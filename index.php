<?php
/**
 * Raptio — index.php
 * パーマリンク設定に対応したルーティングとURL生成ヘルパーを含む
 */

define('INDEX_FILE',       __DIR__ . '/data/posts_index.json');
define('PAGES_INDEX_FILE', __DIR__ . '/data/pages_index.json');
define('CONFIG_FILE',      __DIR__ . '/data/site_config.json');
define('CATEGORIES_FILE',  __DIR__ . '/data/categories.json');
define('INCLUDES_DIR',     __DIR__ . '/includes');
define('SITE_ROOT',        __DIR__);

require_once INCLUDES_DIR . '/widget-manager.php';

if (!file_exists(CONFIG_FILE)) {
    header('Location: setup.php');
    exit;
}

// ── 設定読み込み ──────────────────────────────────────────────
$site_config_raw = json_decode(file_get_contents(CONFIG_FILE), true);
$default_config  = [
    'active_theme'        => 'lux',
    'site_name'           => 'Raptio Site',
    'site_description'    => '',
    'footer_text'         => 'Raptio. All rights reserved.',
    'sidebar_title'       => '',
    'sidebar_content'     => '',
    'permalink_structure' => '/%postname%/',
];
$site_config = array_merge($default_config, is_array($site_config_raw) ? $site_config_raw : []);

$permalink_structure = $site_config['permalink_structure'];

// ── データ読み込み ────────────────────────────────────────────
$posts_raw      = file_exists(INDEX_FILE)      ? json_decode(file_get_contents(INDEX_FILE),      true) : [];
$posts          = is_array($posts_raw) ? $posts_raw : [];

$cats_raw       = file_exists(CATEGORIES_FILE) ? json_decode(file_get_contents(CATEGORIES_FILE), true) : [];
$all_categories = is_array($cats_raw) ? $cats_raw : [];

// ── テーマ ────────────────────────────────────────────────────
$current_theme = $site_config['active_theme'];
define('THEME_DIR', __DIR__ . "/themes/{$current_theme}");

// テーマに functions.php があれば読み込む
if (file_exists(THEME_DIR . '/functions.php')) {
    require_once THEME_DIR . '/functions.php';
}

function get_template_part(string $part_name): void {
    global $site_config, $posts, $post_meta, $content, $all_categories, $base_path,
           $req_category, $req_cpt, $current_category;
    $file = THEME_DIR . "/{$part_name}.php";
    if (file_exists($file)) include $file;
}

// ────────────────────────────────────────────────────────────────
// get_permalink($post_meta)
// パーマリンク構造に従ってURLを生成する。
// 戻り値は常に '/' 始まりの絶対パス（サブディレクトリ分は含まない）。
// テーマ側で $base_path を前置すること。
// ────────────────────────────────────────────────────────────────
function get_permalink(array $post_meta): string {
    global $permalink_structure, $all_categories;

    $structure = $permalink_structure ?? '/%postname%/';
    $slug      = $post_meta['slug']     ?? '';
    $post_id   = $post_meta['id']       ?? ($post_meta['post_id'] ?? '');
    $date      = $post_meta['date']     ?? ($post_meta['published_at'] ?? '');

    // 基本パーマリンク
    if ($structure === 'plain' || $structure === '') {
        return '/?p=' . urlencode((string)$post_id);
    }

    // 日付パーツ
    $dt        = $date ? date_create($date) : null;
    $year      = $dt ? date_format($dt, 'Y') : '0000';
    $monthnum  = $dt ? date_format($dt, 'm') : '00';
    $day       = $dt ? date_format($dt, 'd') : '00';
    $hour      = $dt ? date_format($dt, 'H') : '00';
    $minute    = $dt ? date_format($dt, 'i') : '00';
    $second    = $dt ? date_format($dt, 's') : '00';

    // カテゴリースラッグ（先頭カテゴリー）
    $category_slug = '';
    if (!empty($post_meta['category_id'])) {
        $cat_ids = [$post_meta['category_id']];
    } else {
        $cat_ids = $post_meta['categories'] ?? $post_meta['category_ids'] ?? [];
    }
    if (!empty($cat_ids) && is_array($all_categories)) {
        $first_cat_id = is_array($cat_ids) ? $cat_ids[0] : $cat_ids;
        foreach ($all_categories as $cat) {
            if (($cat['id'] ?? null) == $first_cat_id) {
                $category_slug = $cat['slug'] ?? '';
                break;
            }
        }
    }

    // タグ置換
    $url = str_replace(
        ['%year%', '%monthnum%', '%day%', '%hour%', '%minute%', '%second%', '%post_id%', '%postname%', '%category%'],
        [$year,    $monthnum,    $day,    $hour,    $minute,    $second,    $post_id,    $slug,        $category_slug],
        $structure
    );

    // 先頭スラッシュを確保し、末尾スラッシュも正規化
    return '/' . ltrim($url, '/');
}

// ────────────────────────────────────────────────────────────────
// find_post_by — 通常投稿 → 固定ページ → CPT の順に検索
// ────────────────────────────────────────────────────────────────
function find_post_by(string $field, $value): ?array {
    global $posts;

    // 通常投稿
    foreach ($posts as $post) {
        if (isset($post[$field]) && (string)$post[$field] === (string)$value) return $post;
    }

    // 固定ページ
    $pages_raw = file_exists(PAGES_INDEX_FILE)
        ? json_decode(file_get_contents(PAGES_INDEX_FILE), true)
        : [];
    foreach ((is_array($pages_raw) ? $pages_raw : []) as $page) {
        if (isset($page[$field]) && (string)$page[$field] === (string)$value) return $page;
    }

    // カスタム投稿タイプ
    $cpt_config_file = SITE_ROOT . '/data/cpt_config.json';
    $cpt_config = file_exists($cpt_config_file)
        ? json_decode(file_get_contents($cpt_config_file), true)
        : [];
    if (is_array($cpt_config)) {
        foreach (array_keys($cpt_config) as $cpt_type) {
            $cpt_index_file = SITE_ROOT . "/data/posts_{$cpt_type}_index.json";
            if (!file_exists($cpt_index_file)) continue;
            $cpt_posts = json_decode(file_get_contents($cpt_index_file), true);
            if (!is_array($cpt_posts)) continue;
            foreach ($cpt_posts as $cpt_post) {
                if (isset($cpt_post[$field]) && (string)$cpt_post[$field] === (string)$value) return $cpt_post;
            }
        }
    }

    return null;
}

// ── リクエストパラメータ ──────────────────────────────────────
$req_slug     = isset($_GET['slug'])     ? preg_replace('/[^a-zA-Z0-9\-_.]/', '', $_GET['slug'])     : '';
$req_post_id  = isset($_GET['post_id'])  ? preg_replace('/[^a-zA-Z0-9\-_.]/', '', $_GET['post_id']) : '';
$req_p        = isset($_GET['p'])        ? preg_replace('/[^a-zA-Z0-9\-_.]/', '', $_GET['p'])        : '';
$req_category = isset($_GET['category']) ? preg_replace('/[^a-zA-Z0-9\-_.]/', '', $_GET['category']) : '';
$req_cpt      = isset($_GET['cpt'])      ? preg_replace('/[^a-zA-Z0-9\-_.]/', '', $_GET['cpt'])      : '';

// ── 単一投稿表示 ─────────────────────────────────────────────
$post_meta = null;

if ($req_slug !== '') {
    $post_meta = find_post_by('slug', $req_slug);
} elseif ($req_post_id !== '') {
    $post_meta = find_post_by('id', $req_post_id);
} elseif ($req_p !== '') {
    $post_meta = find_post_by('id', $req_p);
}

if ($post_meta) {
    // 公開状態チェック
    if (($post_meta['status'] ?? '') !== 'public') {
        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
        exit;
    }

    // file_path 正規化
    $raw_path = str_replace('\\', '/', $post_meta['file_path'] ?? '');
    if (preg_match('/^[A-Za-z]:\//', $raw_path)) {
        $data_pos  = strpos($raw_path, '/data/');
        $file_path = $data_pos !== false ? __DIR__ . substr($raw_path, $data_pos) : $raw_path;
    } elseif (str_starts_with($raw_path, '/')) {
        $file_path = $raw_path;
    } else {
        $file_path = __DIR__ . '/' . ltrim($raw_path, '/');
    }

    $content = '<p>記事本文のファイルが見つかりません。</p>';
    if (file_exists($file_path)) {
        $markdown_raw = file_get_contents($file_path);
        // Front Matter 除去
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

    $theme_single = THEME_DIR . '/single.php';
    if (file_exists($theme_single)) {
        include $theme_single;
    } else {
        echo 'テーマファイル (single.php) が見つかりません。';
    }

// ── CPTアーカイブ ────────────────────────────────────────────
} elseif ($req_cpt) {
    $theme_archive = THEME_DIR . '/archive.php';
    if (file_exists($theme_archive)) {
        include $theme_archive;
    } else {
        include THEME_DIR . '/index.php';
    }

// ── カテゴリーアーカイブ ──────────────────────────────────────
} elseif ($req_category) {
    $current_category = null;
    foreach ($all_categories as $cat) {
        if (($cat['slug'] ?? '') === $req_category) {
            $current_category = $cat;
            break;
        }
    }

    $theme_archive = THEME_DIR . '/archive.php';
    if (file_exists($theme_archive)) {
        include $theme_archive;
    } else {
        include THEME_DIR . '/index.php';
    }

// ── トップページ ─────────────────────────────────────────────
} else {
    $theme_index = THEME_DIR . '/index.php';
    if (file_exists($theme_index)) {
        include $theme_index;
    } else {
        echo 'テーマファイル (index.php) が見つかりません。';
    }
}