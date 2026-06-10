<?php
// admin/plugin-page.php
// プラグインページのルーター
// URL: /admin/plugin-page.php?plugin=wp-importer

require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['raptio_auth'])) {
    header('Location: login.php');
    exit;
}

$plugin = basename($_GET['plugin'] ?? '');
if (empty($plugin)) {
    header('Location: index.php');
    exit;
}

// パターン1: plugins/wp-importer/wp-importer-page.php が存在する場合は include
$page_file = PLUGINS_DIR . '/' . $plugin . '/' . $plugin . '-page.php';
if (file_exists($page_file)) {
    require_once $page_file;
    exit;
}

// パターン2: プラグイン本体を require 済みのため、インスタンスの render_page() を呼ぶ
// config.php でプラグイン本体はすでに読み込まれている
// グローバル変数 $xxx_instance または RaptioHook 経由で render_page を呼ぶ
// 規約: プラグインは $GLOBALS["{$plugin}_instance"] にインスタンスを保持する
$instance_key = str_replace('-', '_', $plugin) . '_instance';
if (isset($GLOBALS[$instance_key]) && method_exists($GLOBALS[$instance_key], 'render_page')) {
    $page_title = ''; // プラグイン側で上書き可
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/sidebar.php';
    $GLOBALS[$instance_key]->render_page();
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// どちらも見つからない場合は404
http_response_code(404);
$page_title = 'ページが見つかりません';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
echo '<div style="padding:40px;"><h2>404 - プラグインページが見つかりません</h2><p>プラグイン: ' . htmlspecialchars($plugin, ENT_QUOTES, 'UTF-8') . '</p></div>';
require_once __DIR__ . '/includes/footer.php';