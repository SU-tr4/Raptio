<?php
/**
 * Raptio
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

// 定数定義
define('INDEX_FILE', __DIR__ . '/data/posts_index.json');
define('PAGES_INDEX_FILE', __DIR__ . '/data/pages_index.json');
define('CONFIG_FILE', __DIR__ . '/data/site_config.json');
define('CATEGORIES_FILE', __DIR__ . '/data/categories.json');
define('INCLUDES_DIR', __DIR__ . '/includes');
define('SITE_ROOT', __DIR__);

// 共通ロジックの読み込み
require_once INCLUDES_DIR . '/widget-manager.php';

if (!file_exists(CONFIG_FILE)) {
    header('Location: setup.php');
    exit;
}

// 設定・データの読み込み
$site_config_raw = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
$default_config = [
    'active_theme' => 'lux',
    'site_name' => 'Parallel',
    'site_description' => 'ドキュメント ＆ アップデート情報の集約プラットフォーム',
    'footer_text' => 'Parallel Project. All rights reserved.',
    'sidebar_title' => '',
    'sidebar_content' => ''
];
$site_config = array_merge($default_config, is_array($site_config_raw) ? $site_config_raw : []);

// 記事データの読み込み
$posts_raw = file_exists(INDEX_FILE) ? json_decode(file_get_contents(INDEX_FILE), true) : [];
$posts = is_array($posts_raw) ? $posts_raw : [];

// カテゴリーデータの読み込み
$cats_raw = file_exists(CATEGORIES_FILE) ? json_decode(file_get_contents(CATEGORIES_FILE), true) : [];
$all_categories = is_array($cats_raw) ? $cats_raw : [];

// テーマディレクトリの設定
$current_theme = $site_config['active_theme'];
define('THEME_DIR', __DIR__ . "/themes/{$current_theme}");

// 共通パーツ呼び出し関数 (グローバル変数を渡す)
function get_template_part($part_name) {
    global $site_config, $posts, $post_meta, $content, $all_categories;
    $file = THEME_DIR . "/{$part_name}.php";
    if (file_exists($file)) {
        include $file;
    }
}

// ルーティング処理
$slug = $_GET['slug'] ?? '';
$slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);

if ($slug) {
    $post_meta = null;

    // 1. 通常投稿から検索
    foreach ($posts as $post) {
        if ($post['slug'] === $slug) {
            $post_meta = $post;
            break;
        }
    }

    // 2. 単独ページから検索
    if (!$post_meta) {
        $pages_raw = file_exists(PAGES_INDEX_FILE) ? json_decode(file_get_contents(PAGES_INDEX_FILE), true) : [];
        $pages_all = is_array($pages_raw) ? $pages_raw : [];
        foreach ($pages_all as $page) {
            if ($page['slug'] === $slug) {
                $post_meta = $page;
                break;
            }
        }
    }

    // 3. カスタム投稿タイプから検索
    if (!$post_meta) {
        $cpt_config_file = __DIR__ . '/data/cpt_config.json';
        $cpt_config = file_exists($cpt_config_file) ? json_decode(file_get_contents($cpt_config_file), true) : [];
        if (is_array($cpt_config)) {
            foreach (array_keys($cpt_config) as $cpt_type) {
                $cpt_index_file = __DIR__ . "/data/posts_{$cpt_type}_index.json";
                if (!file_exists($cpt_index_file)) continue;
                $cpt_posts = json_decode(file_get_contents($cpt_index_file), true);
                if (!is_array($cpt_posts)) continue;
                foreach ($cpt_posts as $cpt_post) {
                    if ($cpt_post['slug'] === $slug) {
                        $post_meta = $cpt_post;
                        break 2;
                    }
                }
            }
        }
    }

    if (!$post_meta || ($post_meta['status'] ?? '') !== 'public') {
        header("HTTP/1.1 404 Not Found");
        echo "<h1>404 Not Found</h1>";
        exit;
    }

    // file_pathの正規化: Windowsパス・絶対パス・相対パス全対応
    $raw_path = str_replace('\\', '/', $post_meta['file_path']);
    if (preg_match('/^[A-Za-z]:\//', $raw_path)) {
        // Windowsの絶対パス(C:/xampp/...) → SITE_ROOTを基準に data/ 以降を抽出して再構築
        $data_pos = strpos($raw_path, '/data/');
        $file_path = $data_pos !== false
            ? __DIR__ . substr($raw_path, $data_pos)
            : $raw_path;
    } elseif (str_starts_with($raw_path, '/')) {
        // Linuxの絶対パス
        $file_path = $raw_path;
    } else {
        // 相対パス
        $file_path = __DIR__ . '/' . $raw_path;
    }
    $content = "記事本文のファイルが見つかりません。";
    
    if (file_exists($file_path)) {
        $markdown_raw = file_get_contents($file_path);
        // Front Matter (--- ... ---) を除去
        if (str_starts_with(ltrim($markdown_raw), '---')) {
            $markdown_raw = preg_replace('/^---[\s\S]*?---\s*/m', '', ltrim($markdown_raw), 1);
        }
        if (file_exists(INCLUDES_DIR . '/Parsedown.php')) {
            require_once INCLUDES_DIR . '/Parsedown.php';
            $parsedown = new Parsedown();
            $parsedown->setSafeMode(false);
            $content = $parsedown->text($markdown_raw);
        } else {
            $content = nl2br(htmlspecialchars($markdown_raw, ENT_QUOTES, 'UTF-8'));
        }
    }
    
    $theme_single = THEME_DIR . "/single.php";
    if (file_exists($theme_single)) {
        include $theme_single;
    } else {
        echo "テーマファイル (single.php) が見つかりません。";
    }
} else {
    $theme_index = THEME_DIR . "/index.php";
    if (file_exists($theme_index)) {
        include $theme_index;
    } else {
        echo "テーマファイル (index.php) が見つかりません。";
    }
}