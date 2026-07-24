<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $name = env('DB_NAME', 'teacherdesk_local');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');

        try {
            self::$connection = new PDO(
                "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            SchemaManager::migrate(self::$connection);
        } catch (PDOException $e) {
            http_response_code(500);
            $setupLink = is_file(dirname(__DIR__) . '/setup.php') ? '<p><a href="setup.php">Buka halaman instalasi</a></p>' : '';
            exit('<!doctype html><html lang="id"><meta charset="utf-8"><title>Koneksi gagal</title><style>body{font-family:Arial;max-width:720px;margin:60px auto;padding:24px;color:#1f2937}code{background:#f3f4f6;padding:2px 6px;border-radius:4px}.box{border:1px solid #e5e7eb;border-radius:14px;padding:24px}</style><div class="box"><h1>Database belum terhubung</h1><p>Pastikan MySQL aktif dan file <code>.env</code> sudah benar.</p>' . $setupLink . '<small>' . e($e->getMessage()) . '</small></div></html>');
        }

        return self::$connection;
    }
}
