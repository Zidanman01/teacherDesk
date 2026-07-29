<?php

declare(strict_types=1);

final class MigrationService
{
    public function __construct(
        private PDO $pdo,
        private string $migrationPath
    ) {
    }

    public function migrate(): array
    {
        $this->createMigrationTable();

        $files = glob($this->migrationPath . '/*.sql') ?: [];
        sort($files, SORT_NATURAL);

        $executed = [];

        foreach ($files as $file) {
            $version = basename($file, '.sql');

            if ($this->hasRun($version)) {
                continue;
            }

            $sql = file_get_contents($file);

            if ($sql === false || trim($sql) === '') {
                throw new RuntimeException(
                    "File migrasi {$version} tidak dapat dibaca."
                );
            }

            $this->pdo->beginTransaction();

            try {
                $this->pdo->exec($sql);

                $statement = $this->pdo->prepare(
                    'INSERT INTO schema_migrations (version)
                     VALUES (:version)'
                );

                $statement->execute(['version' => $version]);

                $this->pdo->commit();
                $executed[] = $version;
            } catch (Throwable $exception) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                throw new RuntimeException(
                    "Migrasi {$version} gagal: {$exception->getMessage()}",
                    0,
                    $exception
                );
            }
        }

        return $executed;
    }

    private function createMigrationTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(100) NOT NULL PRIMARY KEY,
                executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function hasRun(string $version): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM schema_migrations WHERE version = :version'
        );

        $statement->execute(['version' => $version]);

        return (int) $statement->fetchColumn() > 0;
    }
}