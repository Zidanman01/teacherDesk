<?php
declare(strict_types=1);
if(session_status()!==PHP_SESSION_ACTIVE){session_start();}
require_once __DIR__.'/app/helpers.php';
$message='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $host=trim((string)($_POST['host']??'127.0.0.1'));$port=trim((string)($_POST['port']??'3306'));$dbName=trim((string)($_POST['db_name']??'teacherdesk_local'));$user=trim((string)($_POST['db_user']??'root'));$pass=(string)($_POST['db_pass']??'');
    try{
        if(!preg_match('/^[A-Za-z0-9_]+$/',$dbName)){throw new RuntimeException('Nama database hanya boleh memakai huruf, angka, dan garis bawah.');}
        $pdo=new PDO("mysql:host={$host};port={$port};charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");
        $sql=file_get_contents(__DIR__.'/database/database.sql');
        if($sql===false){throw new RuntimeException('File skema database tidak ditemukan.');}
        $statements=preg_split('/;\s*(?:\r?\n|$)/',$sql,-1,PREG_SPLIT_NO_EMPTY)?:[];
        foreach($statements as $statement){$statement=trim($statement);if($statement!==''){$pdo->exec($statement);}}
        $quote=static fn(string $v):string=>'"'.str_replace(['\\','"'],['\\\\','\\"'],$v).'"';
        $env="APP_NAME=TeacherDesk Lokal Desktop\nAPP_URL=http://localhost/teacherdesk-local-desktop\nAPP_TIMEZONE=Asia/Jakarta\nDB_HOST={$quote($host)}\nDB_PORT={$quote($port)}\nDB_NAME={$quote($dbName)}\nDB_USER={$quote($user)}\nDB_PASS={$quote($pass)}\n";
        if(file_put_contents(__DIR__.'/.env',$env)===false){throw new RuntimeException('Tidak dapat menulis file .env. Periksa izin folder aplikasi.');}
        $message='Instalasi berhasil. Database dan data demonstrasi sudah dibuat.';
    }catch(Throwable $e){$error=$e->getMessage();}
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Instalasi TeacherDesk Lokal Desktop</title><link rel="stylesheet" href="assets/css/app.css"></head><body class="setup-page"><div class="setup-card"><div class="setup-brand"><div class="brand-mark">TD</div><div><strong>Instalasi TeacherDesk Lokal</strong><div class="muted text-sm">Konfigurasi MySQL atau MariaDB</div></div></div><?php if($message): ?><div class="alert alert-success"><span><?= e($message) ?></span></div><a class="btn btn-primary w-full" href="index.php">Buka aplikasi</a><div class="setup-hint">Aplikasi akan terbuka langsung pada dashboard tanpa halaman login.</div><?php else: ?><?php if($error): ?><div class="alert alert-danger"><span><?= e($error) ?></span></div><?php endif; ?><p class="muted text-sm">Pastikan Apache dan MySQL sudah aktif di Laragon atau XAMPP. Installer akan membuat database dan data demonstrasi.</p><form method="post"><?= csrf_field() ?><div class="form-grid"><div class="form-group"><label>Host database</label><input class="form-control" name="host" value="127.0.0.1" required></div><div class="form-group"><label>Port</label><input class="form-control" name="port" value="3306" required></div><div class="form-group full"><label>Nama database</label><input class="form-control" name="db_name" value="teacherdesk_local" required></div><div class="form-group"><label>Pengguna database</label><input class="form-control" name="db_user" value="root" required></div><div class="form-group"><label>Kata sandi database</label><input class="form-control" type="password" name="db_pass" value=""></div></div><button class="btn btn-primary w-full mt-3" type="submit">Pasang aplikasi</button></form><div class="setup-hint">Pada konfigurasi standar Laragon atau XAMPP, pengguna biasanya <strong>root</strong> dan kata sandi dibiarkan kosong.</div><?php endif; ?></div></body></html>
