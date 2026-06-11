<?php
// admin/api.php
// 致命的なエラーをキャッチしてJSONとして出力する設定
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Fatal Error', 'message' => $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']]);
        exit;
    }
});

// エラー抑制
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';
session_start();

// 出力バッファリング
ob_start();

function json_response($data, $status = 200) {
    if (ob_get_length()) ob_end_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// 認証チェック
if (!isset($_SESSION['raptio_auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($username === ADMIN_USER && password_verify($password, ADMIN_PASS)) {
            $_SESSION['raptio_auth'] = true;
            json_response(['success' => true]);
        } else {
            json_response(['success' => false, 'message' => 'ユーザー名またはパスワードが違います。']);
        }
    }
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

// アクションの取得
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================================
// 投稿 (Post) アクション
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_post') {
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $thumbnail = $_POST['thumbnail'] ?? '';
    $is_new = empty($id);
    
    if (empty($title)) json_response(['success' => false, 'message' => 'タイトルを入力してください。']);
    if (empty($slug)) $slug = $id; // フォールバック
    if (!preg_match('/^[a-zA-Z0-9\-_#]+$/', $slug)) json_response(['success' => false, 'message' => 'スラッグは半角英数字、ハイフン、アンダースコアのみ使用できます。']);
    
    if ($is_new) {
        $id = uniqid();
        $file_name = $id . '.md';
    } else {
        $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true);
        $file_name = '';
        foreach ($index_data as $p) { if ($p['id'] === $id) { $file_name = basename($p['file_path']); break; } }
        if (empty($file_name)) $file_name = $id . '.md';
    }
    
    $file_path = DATA_DIR . '/posts/' . $file_name;
    if (!is_dir(dirname($file_path))) @mkdir(dirname($file_path), 0755, true);
    
    // Front Matter と 本文の組み立て
    $fm_status = $_POST['status'] ?? 'draft';
    $fm_date = $is_new ? date('Y-m-d H:i:s') : ($_POST['date'] ?? date('Y-m-d H:i:s'));
    $fm_cat = $_POST['category_id'] ?? '';
    
    $md_content = "---\n";
    $md_content .= "id: \"{$id}\"\n";
    $md_content .= "title: " . json_encode($title, JSON_UNESCAPED_UNICODE) . "\n";
    $md_content .= "slug: " . json_encode($slug, JSON_UNESCAPED_UNICODE) . "\n";
    $md_content .= "date: \"{$fm_date}\"\n";
    $md_content .= "status: \"{$fm_status}\"\n";
    $md_content .= "category: \"{$fm_cat}\"\n";
    $md_content .= "thumbnail: \"{$thumbnail}\"\n";
    $md_content .= "---\n\n";
    $md_content .= $content;
    
    file_put_contents($file_path, $md_content);
    
    // インデックスの更新
    $index_data = file_exists(INDEX_FILE) ? json_decode(file_get_contents(INDEX_FILE), true) : [];
    $post_meta = ['id' => $id, 'slug' => $slug, 'title' => $title, 'date' => $is_new ? date('Y-m-d H:i:s') : ($_POST['date'] ?? date('Y-m-d H:i:s')), 'status' => $_POST['status'] ?? 'draft', 'category_id' => $_POST['category_id'] ?? '', 'thumbnail' => $thumbnail, 'file_path' => $file_path];
    
    if ($is_new) {
        $index_data[] = $post_meta;
    } else {
        foreach ($index_data as &$p) { if ($p['id'] === $id) $p = array_merge($p, $post_meta); }
    }
    
    file_put_contents(INDEX_FILE, json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true, 'id' => $id]);
}

// 従来のdeleteアクション（念のため維持し、中身を完全削除のロジックにマッピング）
if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true);
    $filtered = [];
    foreach ($index_data as $p) {
        if ($p['id'] === $id) {
            @unlink($p['file_path']);
        } else {
            $filtered[] = $p;
        }
    }
    file_put_contents(INDEX_FILE, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'trash') {
    $id = $_POST['id'] ?? '';
    $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true);
    foreach ($index_data as &$p) {
        if ($p['id'] === $id) {
            $p['status'] = 'trash';
            if (file_exists($p['file_path'])) {
                $content = file_get_contents($p['file_path']);
                $content = preg_replace('/^status:\s*".*?"/m', 'status: "trash"', $content);
                file_put_contents($p['file_path'], $content);
            }
            break;
        }
    }
    file_put_contents(INDEX_FILE, json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'restore') {
    $id = $_POST['id'] ?? '';
    $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true);
    foreach ($index_data as &$p) {
        if ($p['id'] === $id) {
            $p['status'] = 'draft';
            if (file_exists($p['file_path'])) {
                $content = file_get_contents($p['file_path']);
                $content = preg_replace('/^status:\s*".*?"/m', 'status: "draft"', $content);
                file_put_contents($p['file_path'], $content);
            }
            break;
        }
    }
    file_put_contents(INDEX_FILE, json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'delete_permanently') {
    $id = $_POST['id'] ?? '';
    $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true);
    $filtered = [];
    foreach ($index_data as $p) {
        if ($p['id'] === $id) {
            if (file_exists($p['file_path'])) {
                @unlink($p['file_path']);
            }
        } else {
            $filtered[] = $p;
        }
    }
    file_put_contents(INDEX_FILE, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'bulk_trash') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (!is_array($ids)) json_response(['success' => false, 'message' => 'Invalid IDs']);
    
    $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true);
    foreach ($index_data as &$p) {
        if (in_array($p['id'], $ids)) {
            $p['status'] = 'trash';
            if (file_exists($p['file_path'])) {
                $content = file_get_contents($p['file_path']);
                $content = preg_replace('/^status:\s*".*?"/m', 'status: "trash"', $content);
                file_put_contents($p['file_path'], $content);
            }
        }
    }
    file_put_contents(INDEX_FILE, json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'bulk_restore') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (!is_array($ids)) json_response(['success' => false, 'message' => 'Invalid IDs']);
    
    $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true);
    foreach ($index_data as &$p) {
        if (in_array($p['id'], $ids)) {
            $p['status'] = 'draft';
            if (file_exists($p['file_path'])) {
                $content = file_get_contents($p['file_path']);
                $content = preg_replace('/^status:\s*".*?"/m', 'status: "draft"', $content);
                file_put_contents($p['file_path'], $content);
            }
        }
    }
    file_put_contents(INDEX_FILE, json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'bulk_delete_permanently') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (!is_array($ids)) json_response(['success' => false, 'message' => 'Invalid IDs']);
    
    $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true);
    $filtered = [];
    foreach ($index_data as $p) {
        if (in_array($p['id'], $ids)) {
            if (file_exists($p['file_path'])) {
                @unlink($p['file_path']);
            }
        } else {
            $filtered[] = $p;
        }
    }
    file_put_contents(INDEX_FILE, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

// ============================================================
// 単独ページ (Page) アクション
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_page') {
    $id        = $_POST['id'] ?? '';
    $title     = trim($_POST['title'] ?? '');
    $slug      = trim($_POST['slug'] ?? '');
    $content   = $_POST['content'] ?? '';
    $thumbnail = $_POST['thumbnail'] ?? '';
    $is_new    = empty($id);

    if (empty($title)) json_response(['success' => false, 'message' => 'タイトルを入力してください。']);
    if (empty($slug))  json_response(['success' => false, 'message' => 'スラッグを入力してください。']);
    if (!preg_match('/^[a-zA-Z0-9\-_#]+$/', $slug)) json_response(['success' => false, 'message' => 'スラッグは半角英数字、ハイフン、アンダースコアのみ使用できます。']);

    // スラッグの重複チェック（自分以外）
    $pages_index = file_exists(PAGES_INDEX_FILE) ? json_decode(file_get_contents(PAGES_INDEX_FILE), true) : [];
    foreach ($pages_index as $pg) {
        if ($pg['slug'] === $slug && $pg['id'] !== $id) {
            json_response(['success' => false, 'message' => 'このスラッグはすでに使用されています。']);
        }
    }

    if ($is_new) {
        $id        = 'page_' . uniqid();
        $file_name = $id . '.md';
    } else {
        $file_name = '';
        foreach ($pages_index as $pg) {
            if ($pg['id'] === $id) { $file_name = basename($pg['file_path']); break; }
        }
        if (empty($file_name)) $file_name = $id . '.md';
    }

    $file_path = PAGES_DIR . '/' . $file_name;
    if (!is_dir(PAGES_DIR)) @mkdir(PAGES_DIR, 0755, true);

    $fm_status = $_POST['status'] ?? 'draft';
    $fm_date   = $is_new ? date('Y-m-d H:i:s') : ($_POST['date'] ?? date('Y-m-d H:i:s'));

    $md_content  = "---\n";
    $md_content .= "id: \"{$id}\"\n";
    $md_content .= "title: " . json_encode($title, JSON_UNESCAPED_UNICODE) . "\n";
    $md_content .= "slug: " . json_encode($slug, JSON_UNESCAPED_UNICODE) . "\n";
    $md_content .= "date: \"{$fm_date}\"\n";
    $md_content .= "status: \"{$fm_status}\"\n";
    $md_content .= "thumbnail: \"{$thumbnail}\"\n";
    $md_content .= "type: \"page\"\n";
    $md_content .= "---\n\n";
    $md_content .= $content;

    file_put_contents($file_path, $md_content);

    // インデックスの更新
    $page_meta = [
        'id'        => $id,
        'slug'      => $slug,
        'title'     => $title,
        'date'      => $is_new ? date('Y-m-d H:i:s') : ($_POST['date'] ?? date('Y-m-d H:i:s')),
        'status'    => $fm_status,
        'thumbnail' => $thumbnail,
        'file_path' => $file_path,
        'type'      => 'page',
    ];

    if ($is_new) {
        $pages_index[] = $page_meta;
    } else {
        foreach ($pages_index as &$pg) {
            if ($pg['id'] === $id) { $pg = array_merge($pg, $page_meta); break; }
        }
    }

    file_put_contents(PAGES_INDEX_FILE, json_encode($pages_index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true, 'id' => $id]);
}

if ($action === 'trash_page') {
    $id          = $_POST['id'] ?? '';
    $pages_index = json_decode(file_exists(PAGES_INDEX_FILE) ? file_get_contents(PAGES_INDEX_FILE) : '[]', true);
    foreach ($pages_index as &$pg) {
        if ($pg['id'] === $id) {
            $pg['status'] = 'trash';
            if (file_exists($pg['file_path'])) {
                $c = file_get_contents($pg['file_path']);
                $c = preg_replace('/^status:\s*".*?"/m', 'status: "trash"', $c);
                file_put_contents($pg['file_path'], $c);
            }
            break;
        }
    }
    file_put_contents(PAGES_INDEX_FILE, json_encode($pages_index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'restore_page') {
    $id          = $_POST['id'] ?? '';
    $pages_index = json_decode(file_exists(PAGES_INDEX_FILE) ? file_get_contents(PAGES_INDEX_FILE) : '[]', true);
    foreach ($pages_index as &$pg) {
        if ($pg['id'] === $id) {
            $pg['status'] = 'draft';
            if (file_exists($pg['file_path'])) {
                $c = file_get_contents($pg['file_path']);
                $c = preg_replace('/^status:\s*".*?"/m', 'status: "draft"', $c);
                file_put_contents($pg['file_path'], $c);
            }
            break;
        }
    }
    file_put_contents(PAGES_INDEX_FILE, json_encode($pages_index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'delete_page_permanently') {
    $id          = $_POST['id'] ?? '';
    $pages_index = json_decode(file_exists(PAGES_INDEX_FILE) ? file_get_contents(PAGES_INDEX_FILE) : '[]', true);
    $filtered    = [];
    foreach ($pages_index as $pg) {
        if ($pg['id'] === $id) {
            if (file_exists($pg['file_path'])) @unlink($pg['file_path']);
        } else {
            $filtered[] = $pg;
        }
    }
    file_put_contents(PAGES_INDEX_FILE, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'bulk_trash_page') {
    $ids         = json_decode($_POST['ids'] ?? '[]', true);
    if (!is_array($ids)) json_response(['success' => false, 'message' => 'Invalid IDs']);
    $pages_index = json_decode(file_exists(PAGES_INDEX_FILE) ? file_get_contents(PAGES_INDEX_FILE) : '[]', true);
    foreach ($pages_index as &$pg) {
        if (in_array($pg['id'], $ids)) {
            $pg['status'] = 'trash';
            if (file_exists($pg['file_path'])) {
                $c = file_get_contents($pg['file_path']);
                $c = preg_replace('/^status:\s*".*?"/m', 'status: "trash"', $c);
                file_put_contents($pg['file_path'], $c);
            }
        }
    }
    file_put_contents(PAGES_INDEX_FILE, json_encode($pages_index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'bulk_restore_page') {
    $ids         = json_decode($_POST['ids'] ?? '[]', true);
    if (!is_array($ids)) json_response(['success' => false, 'message' => 'Invalid IDs']);
    $pages_index = json_decode(file_exists(PAGES_INDEX_FILE) ? file_get_contents(PAGES_INDEX_FILE) : '[]', true);
    foreach ($pages_index as &$pg) {
        if (in_array($pg['id'], $ids)) {
            $pg['status'] = 'draft';
            if (file_exists($pg['file_path'])) {
                $c = file_get_contents($pg['file_path']);
                $c = preg_replace('/^status:\s*".*?"/m', 'status: "draft"', $c);
                file_put_contents($pg['file_path'], $c);
            }
        }
    }
    file_put_contents(PAGES_INDEX_FILE, json_encode($pages_index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'bulk_delete_page_permanently') {
    $ids         = json_decode($_POST['ids'] ?? '[]', true);
    if (!is_array($ids)) json_response(['success' => false, 'message' => 'Invalid IDs']);
    $pages_index = json_decode(file_exists(PAGES_INDEX_FILE) ? file_get_contents(PAGES_INDEX_FILE) : '[]', true);
    $filtered    = [];
    foreach ($pages_index as $pg) {
        if (in_array($pg['id'], $ids)) {
            if (file_exists($pg['file_path'])) @unlink($pg['file_path']);
        } else {
            $filtered[] = $pg;
        }
    }
    file_put_contents(PAGES_INDEX_FILE, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

// ============================================================
// カテゴリ・メディア・設定 アクション（変更なし）
// ============================================================

// カテゴリ一覧取得
if ($action === 'get_categories') {
    $categories = json_decode(file_exists(CATEGORY_FILE) ? file_get_contents(CATEGORY_FILE) : '[]', true);
    json_response($categories);
}

// カテゴリ保存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_category') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    
    if (empty($name)) json_response(['success' => false, 'message' => 'カテゴリ名を入力してください。']);
    if (empty($slug)) $slug = wp_importer_slugify($name);
    
    $categories = json_decode(file_exists(CATEGORY_FILE) ? file_get_contents(CATEGORY_FILE) : '[]', true);
    
    $is_new = empty($id);
    if ($is_new) {
        $id = uniqid();
        $categories[] = ['id' => $id, 'name' => $name, 'slug' => $slug];
    } else {
        foreach ($categories as &$cat) {
            if ($cat['id'] === $id) {
                $cat['name'] = $name;
                $cat['slug'] = $slug;
                break;
            }
        }
    }
    
    file_put_contents(CATEGORY_FILE, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true, 'id' => $id]);
}

// カテゴリ削除
if ($action === 'delete_category') {
    $id = $_POST['id'] ?? '';
    $categories = json_decode(file_exists(CATEGORY_FILE) ? file_get_contents(CATEGORY_FILE) : '[]', true);
    $filtered = [];
    foreach ($categories as $cat) {
        if ($cat['id'] !== $id) $filtered[] = $cat;
    }
    file_put_contents(CATEGORY_FILE, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

// メディアアップロード
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_media') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        json_response(['success' => false, 'message' => 'ファイルのアップロードに失敗しました。']);
    }
    
    $upload_dir = dirname(DATA_DIR) . '/uploads';
    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
    
    $filename = time() . '_' . basename($_FILES['file']['name']);
    $dest = $upload_dir . '/' . $filename;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        json_response(['success' => true, 'url' => 'uploads/' . $filename]);
    } else {
        json_response(['success' => false, 'message' => 'ファイルの移動に失敗しました。']);
    }
}

// メディア一覧
if ($action === 'get_media') {
    $upload_dir = dirname(DATA_DIR) . '/uploads';
    $allowed_ext = ['jpg','jpeg','png','gif','webp','svg'];
    $files = [];
    if (is_dir($upload_dir)) {
        foreach (scandir($upload_dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $fp  = $upload_dir . '/' . $file;
            if (is_dir($fp)) continue;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext, true)) continue;
            $files[] = [
                'url'  => 'uploads/' . $file,
                'name' => $file,
                'date' => date('Y-m-d H:i', filemtime($fp)),
                'size' => round(filesize($fp) / 1024, 1) . ' KB',
            ];
        }
    }
    usort($files, function($a, $b) { return strcmp($b['date'], $a['date']); });
    json_response($files);
}

// 設定保存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_settings') {
    $site_title = $_POST['site_title'] ?? '';
    $site_description = $_POST['site_description'] ?? '';
    $current_theme = $_POST['current_theme'] ?? 'default';
    
    $settings = ['site_title' => $site_title, 'site_description' => $site_description, 'current_theme' => $current_theme];
    file_put_contents(SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

json_response(['error' => 'Bad Request', 'message' => '不明なアクションです。'], 400);