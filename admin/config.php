<?php
// admin/config.php

// 1. 定数の定義（全ファイルの基礎）
define('DATA_DIR', realpath(__DIR__ . '/../data'));
define('POSTS_DIR', DATA_DIR . '/posts');
define('INDEX_FILE', DATA_DIR . '/posts_index.json');
define('PAGES_DIR', DATA_DIR . '/pages');
define('PAGES_INDEX_FILE', DATA_DIR . '/pages_index.json');
define('CONFIG_FILE', DATA_DIR . '/site_config.json');
define('CATEGORY_FILE', DATA_DIR . '/categories.json');
define('CPT_CONFIG_FILE', DATA_DIR . '/cpt_config.json');
define('PLUGINS_DIR', realpath(__DIR__ . '/../plugins'));
define('PLUGINS_JSON', DATA_DIR . '/plugins.json');

// 2. セットアップ確認
if (!file_exists(CONFIG_FILE)) {
    header('Location: ../setup.php');
    exit;
}

// 3. ディレクトリ・ファイルの生成
if (!is_dir(POSTS_DIR)) mkdir(POSTS_DIR, 0755, true);
if (!is_dir(PAGES_DIR)) mkdir(PAGES_DIR, 0755, true);
if (!is_dir(PLUGINS_DIR)) mkdir(PLUGINS_DIR, 0755, true);
if (!file_exists(INDEX_FILE)) file_put_contents(INDEX_FILE, json_encode([]));
if (!file_exists(PAGES_INDEX_FILE)) file_put_contents(PAGES_INDEX_FILE, json_encode([]));
if (!file_exists(PLUGINS_JSON)) file_put_contents(PLUGINS_JSON, json_encode([]));
if (!file_exists(CPT_CONFIG_FILE)) file_put_contents(CPT_CONFIG_FILE, json_encode([]));

// 4. フックAPIの読み込み
require_once __DIR__ . '/includes/plugin-api.php';

// 5. プラグイン読み込みロジック
$active_plugins_data = [];
if (file_exists(PLUGINS_JSON)) {
    $json_content = file_get_contents(PLUGINS_JSON);
    $decoded = json_decode($json_content, true);
    $active_plugins_data = is_array($decoded) ? $decoded : [];
}

foreach ($active_plugins_data as $key => $val) {
    // 形式判定: 連想配列(新)か単純なリスト(旧)か
    $folder_name = '';
    $is_active = false;

    if (is_array($val) && array_key_exists('active', $val)) {
        // 新形式: {"folder_name": {"name": "...", "active": true, ...}}
        $folder_name = $key;
        $is_active = (bool)$val['active'];
    } else {
        // 旧形式: ["folder_name1", "folder_name2"]
        // $val はフォルダ名そのもの
        $folder_name = $val;
        $is_active = true; // 旧形式は全てアクティブとみなす
    }

    // 読み込み処理
    if ($is_active) {
        // 安全にパスを生成
        $safe_folder = basename($folder_name);
        $plugin_file = PLUGINS_DIR . '/' . $safe_folder . '/' . $safe_folder . '.php';
        
        if (file_exists($plugin_file)) {
            require_once $plugin_file;
        }
    }
}