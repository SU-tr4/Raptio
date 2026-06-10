<?php
// admin/auth.php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ログアウト処理
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

// 認証チェック関数
function check_raptio_auth() {
    return isset($_SESSION['raptio_auth']) && $_SESSION['raptio_auth'] === true;
}