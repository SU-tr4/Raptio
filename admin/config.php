<?php
// admin/config.php
define('DATA_DIR', __DIR__ . '/../data');
define('POSTS_DIR', DATA_DIR . '/posts');
define('INDEX_FILE', DATA_DIR . '/posts_index.json');
define('CONFIG_FILE', DATA_DIR . '/site_config.json');
define('PLUGINS_DIR', __DIR__ . '/../plugins');

// セットアップが完了しているか確認（未設定ならセットアップ画面へ）
if (!file_exists(CONFIG_FILE)) {
    header('Location: ../setup.php');
    exit;
}

// 必要なディレクトリの生成
if (!is_dir(POSTS_DIR)) mkdir(POSTS_DIR, 0755, true);
if (!is_dir(PLUGINS_DIR)) mkdir(PLUGINS_DIR, 0755, true);
if (!is_dir(__DIR__ . '/../uploads')) mkdir(__DIR__ . '/../uploads', 0755, true);
if (!file_exists(INDEX_FILE)) file_put_contents(INDEX_FILE, json_encode([]));

// プラグイン読み込み
require_once __DIR__ . '/plugin-helper.php';
$plugin_files = glob(PLUGINS_DIR . '/*.php');
if ($plugin_files) {
    foreach ($plugin_files as $file) {
        include_once $file;
    }
}