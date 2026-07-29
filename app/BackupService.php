<?php
declare(strict_types=1);

final class BackupService
{
    private const TABLES = ['subjects','classes','materials','schedule_templates','schedules','teaching_journals','questions','settings'];

    public static function export(PDO $db): never
    {
        $payload = [
            'application' => 'TeacherDesk Lokal',
            'version' => '1.4.0',
            'exported_at' => date(DATE_ATOM),
            'tables' => [],
        ];
        foreach (self::TABLES as $table) {
            $payload['tables'][$table] = $db->query("SELECT * FROM `{$table}` ORDER BY id ASC")->fetchAll();
        }

        $filename = 'teacherdesk-backup-' . date('Y-m-d-His') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    public static function restore(PDO $db, string $tmpFile): void
    {
        $json = file_get_contents($tmpFile);
        $payload = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
        if (($payload['application'] ?? '') !== 'TeacherDesk Lokal' || !isset($payload['tables']) || !is_array($payload['tables'])) {
            throw new RuntimeException('File bukan backup TeacherDesk Lokal yang valid.');
        }
        $db->beginTransaction();
        try {
            $db->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach (array_reverse(self::TABLES) as $table) {
                $db->exec("DELETE FROM `{$table}`");
            }
            foreach (self::TABLES as $table) {
                $rows = $payload['tables'][$table] ?? [];
                if (!is_array($rows)) {
                    continue;
                }
                foreach ($rows as $row) {
                    if (!is_array($row) || !$row) {
                        continue;
                    }
                    $columns = array_keys($row);
                    $columnSql = implode(',', array_map(fn(string $col): string => "`{$col}`", $columns));
                    $placeholders = implode(',', array_fill(0, count($columns), '?'));
                    $stmt = $db->prepare("INSERT INTO `{$table}` ({$columnSql}) VALUES ({$placeholders})");
                    $stmt->execute(array_values($row));
                }
            }
            $db->exec('SET FOREIGN_KEY_CHECKS=1');
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            try { $db->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable) {}
            throw $e;
        }
    }
}
