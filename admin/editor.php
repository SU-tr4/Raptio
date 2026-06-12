<?php
// admin/editor.php
require_once __DIR__ . '/config.php';
session_start();

if (!isset($_SESSION['raptio_auth'])) {
    header('Location: index.php');
    exit;
}

// Front Matter除去ヘルパー
function strip_front_matter(string $text): string {
    $text = ltrim($text);
    if (str_starts_with($text, '---')) {
        $text = preg_replace('/^---[\s\S]*?---\s*/m', '', $text, 1);
    }
    return $text;
}

// モード判定: post（投稿）/ page（単独ページ）/ {cpt_slug}（カスタム投稿タイプ）
$mode = $_GET['mode'] ?? 'post';
$cpt_type = $_GET['type'] ?? '';   // CPTスラッグ（例: "news"）

// typeパラメータがあればCPTモード
if ($cpt_type !== '' && $cpt_type !== 'post' && $cpt_type !== 'page') {
    $mode = 'cpt';
} elseif ($mode !== 'page') {
    $mode = 'post';
}

// CPT設定の読み込み
$cpt_label = '';
if ($mode === 'cpt') {
    $cpt_config = defined('CPT_CONFIG_FILE') && file_exists(CPT_CONFIG_FILE)
        ? json_decode(file_get_contents(CPT_CONFIG_FILE), true) : [];
    $cpt_label  = $cpt_config[$cpt_type]['label'] ?? $cpt_type;
}

// カテゴリ（投稿モードのみ使用）
$all_categories = [];
if ($mode === 'post') {
    $categories_file = DATA_DIR . '/categories.json';
    $all_categories  = file_exists($categories_file) ? json_decode(file_get_contents($categories_file), true) : [];
    if (!is_array($all_categories)) $all_categories = [];
}

$id          = $_GET['id'] ?? '';
$title       = '';
$slug        = '';
$content     = '';
$status      = 'draft';
$date        = '';
$category_id = '';
$thumbnail   = '';

if ($id) {
    if ($mode === 'post') {
        $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true) ?? [];
        foreach ($index_data as $post) {
            if ($post['id'] === $id) {
                $title       = $post['title'];
                $slug        = $post['slug'];
                $status      = $post['status'];
                $date        = $post['date'];
                $category_id = $post['category_id'] ?? '';
                $thumbnail   = $post['thumbnail'] ?? '';
                $full_path   = POSTS_DIR . '/' . basename($post['file_path']);
                if (!file_exists($full_path)) $full_path = __DIR__ . '/../' . $post['file_path'];
                if (file_exists($full_path)) $content = strip_front_matter(file_get_contents($full_path));
                break;
            }
        }
    } elseif ($mode === 'cpt') {
        $cpt_index_file = DATA_DIR . "/posts_{$cpt_type}_index.json";
        $index_data = json_decode(file_exists($cpt_index_file) ? file_get_contents($cpt_index_file) : '[]', true) ?? [];
        foreach ($index_data as $post) {
            if ($post['id'] === $id) {
                $title     = $post['title'];
                $slug      = $post['slug'];
                $status    = $post['status'];
                $date      = $post['date'];
                $thumbnail = $post['thumbnail'] ?? '';
                $fp        = $post['file_path'];
                if (!file_exists($fp)) $fp = __DIR__ . '/../' . $fp;
                if (file_exists($fp)) $content = strip_front_matter(file_get_contents($fp));
                break;
            }
        }
    } else {
        $pages_index = json_decode(file_exists(PAGES_INDEX_FILE) ? file_get_contents(PAGES_INDEX_FILE) : '[]', true) ?? [];
        foreach ($pages_index as $pg) {
            if ($pg['id'] === $id) {
                $title     = $pg['title'];
                $slug      = $pg['slug'];
                $status    = $pg['status'];
                $date      = $pg['date'];
                $thumbnail = $pg['thumbnail'] ?? '';
                $fp        = $pg['file_path'];
                if (!file_exists($fp)) $fp = __DIR__ . '/../' . $pg['file_path'];
                if (file_exists($fp)) $content = strip_front_matter(file_get_contents($fp));
                break;
            }
        }
    }
}

// ページタイトル・サイドバー変数
if ($mode === 'cpt') {
    $page_title   = $id ? $cpt_label . 'の編集' : '新規' . $cpt_label . 'を追加';
    $current_page = 'cpt_' . $cpt_type;
    $sub_page     = 'add';
    $save_action  = 'save_post';
    $list_page    = 'edit-posts.php?type=' . urlencode($cpt_type);
    $h2_label     = $page_title;
} elseif ($mode === 'post') {
    $page_title   = $id ? '投稿の編集' : '新規投稿を追加';
    $current_page = 'posts';
    $sub_page     = 'add';
    $save_action  = 'save_post';
    $list_page    = 'edit-posts.php';
    $h2_label     = $id ? '投稿の編集' : '新規投稿を追加';
} else {
    $page_title   = $id ? '単独ページの編集' : '新規単独ページを追加';
    $current_page = 'pages';
    $sub_page     = 'add';
    $save_action  = 'save_page';
    $list_page    = 'edit-pages.php';
    $h2_label     = $id ? '単独ページの編集' : '新規単独ページを追加';
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<?php require_once __DIR__ . '/includes/editor_form.php'; ?>
<?php require_once __DIR__ . '/includes/editor_scripts.php'; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>