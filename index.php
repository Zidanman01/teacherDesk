<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

$db = Database::connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_action($db);
}

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard','subjects','classes','schedules','materials','journals','questions','generator','backup','settings'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$flashes = consume_flashes();
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/pages/' . $page . '.php';
require __DIR__ . '/includes/footer.php';
