<?php
// index.php (ルート)
define('INDEX_FILE', __DIR__ . '/data/posts_index.json');
define('CONFIG_FILE', __DIR__ . '/data/site_config.json');
define('INCLUDES_DIR', __DIR__ . '/includes');
define('SITE_ROOT', __DIR__); // ★テーマファイルから絶対パスを参照するための定数

// 【追加】初回セットアップのガード節
// 設定ファイルがない場合はセットアップ画面へリダイレクト
if (!file_exists(CONFIG_FILE)) {
    header('Location: setup.php');
    exit;
}

// 洗練された初期テーマ「lux」を指定
$current_theme = 'lux'; 

$slug = $_GET['slug'] ?? '';
$slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);

// テーマに引き渡す記事データの読み込み
$posts_raw = file_exists(INDEX_FILE) ? json_decode(file_get_contents(INDEX_FILE), true) : [];
$posts = is_array($posts_raw) ? $posts_raw : [];

// テーマに引き渡すサイト共通設定の読み込み（デフォルト値を保証）
$default_config = [
    'site_name' => 'Parallel',
    'site_description' => 'ドキュメント ＆ アップデート情報の集約プラットフォーム',
    'header_menu' => "ホーム,index.php\n特徴,#features\nドキュメント,#docs",
    'sidebar_title' => 'ABOUT',
    'sidebar_content' => 'ブラウザ「Parallel」の公式開発ブログです。最新のビルド情報やドキュメントを配信しています。',
    'footer_text' => 'Parallel Project. All rights reserved.'
];
$site_config = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
$site_config = array_merge($default_config, is_array($site_config) ? $site_config : []);

// ルーティング処理
if ($slug) {
    // 記事詳細ページの表示
    $post_meta = null;
    foreach ($posts as $post) {
        if ($post['slug'] === $slug) {
            $post_meta = $post;
            break;
        }
    }

    // 記事が存在しない、または非公開（"public"以外）の場合は404
    if (!$post_meta || ($post_meta['status'] ?? '') !== 'public') {
        header("HTTP/1.1 404 Not Found");
        echo "<h1>404 Not Found</h1><p>ご指定のページは見つかりませんでした。</p>";
        exit;
    }

    $file_path = __DIR__ . '/' . $post_meta['file_path'];
    if (file_exists($file_path)) {
        $markdown_raw = file_get_contents($file_path);
        
        // includes/Parsedown.php からライブラリを読み込んでHTMLに変換
        if (file_exists(INCLUDES_DIR . '/Parsedown.php')) {
            require_once INCLUDES_DIR . '/Parsedown.php';
            $parsedown = new Parsedown();
            $parsedown->setSafeMode(false); 
            $content = $parsedown->text($markdown_raw);
        } else {
            $content = nl2br(htmlspecialchars($markdown_raw, ENT_QUOTES, 'UTF-8'));
        }
    } else {
        $content = "記事本文のファイルが見つかりません。";
    }

    // テーマの詳細ページを読み込み
    $theme_single = __DIR__ . "/themes/{$current_theme}/single.php";
    if (file_exists($theme_single)) {
        include $theme_single;
    } else {
        echo "テーマファイル (single.php) が見つかりません。";
    }
} else {
    // 記事一覧ページの表示
    $theme_index = __DIR__ . "/themes/{$current_theme}/index.php";
    if (file_exists($theme_index)) {
        include $theme_index;
    } else {
        echo "テーマファイル (index.php) が見つかりません。";
    }
}