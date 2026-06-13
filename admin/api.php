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

// 投稿タイプに応じたパスを動的に取得するヘルパー関数
function get_post_paths($type = 'post') {
    if ($type === 'post') {
        return ['index' => INDEX_FILE, 'dir' => POSTS_DIR];
    } else {
        // カスタム投稿用: data/posts_{type}_index.json と data/posts_{type}/ ディレクトリ
        $dir = DATA_DIR . "/posts_{$type}";
        $index = DATA_DIR . "/posts_{$type}_index.json";
        return ['index' => $index, 'dir' => $dir];
    }
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
// 投稿系 (Post & Custom Post) アクション
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_post') {
    $id = $_POST['id'] ?? '';
    $type = $_POST['type'] ?? 'post';
    $paths = get_post_paths($type);

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $thumbnail = $_POST['thumbnail'] ?? '';
    $is_new = empty($id);
    
    if (empty($title)) json_response(['success' => false, 'message' => 'タイトルを入力してください。']);
    if (empty($slug)) {
        // デフォルトスラッグ: 日付 (YYYYMMDD) + 同日の連番
        $date_prefix = date('Ymd');
        $paths_for_slug = get_post_paths($type);
        $index_for_slug = file_exists($paths_for_slug['index']) ? json_decode(file_get_contents($paths_for_slug['index']), true) : [];
        $count = 1;
        foreach ($index_for_slug as $p) {
            if (strpos($p['slug'], $date_prefix) === 0) $count++;
        }
        $slug = $count === 1 ? $date_prefix : $date_prefix . '-' . $count;
    }
    if (!preg_match('/^[a-zA-Z0-9\-_#]+$/', $slug)) json_response(['success' => false, 'message' => 'スラッグは半角英数字、ハイフン、アンダースコアのみ使用できます。']);
    
    $index_data = file_exists($paths['index']) ? json_decode(file_get_contents($paths['index']), true) : [];
    
    if ($is_new) {
        $id = uniqid();
        $file_name = $id . '.md';
    } else {
        $file_name = '';
        foreach ($index_data as $p) { if ($p['id'] === $id) { $file_name = basename($p['file_path']); break; } }
        if (empty($file_name)) $file_name = $id . '.md';
    }
    
    if (!is_dir($paths['dir'])) @mkdir($paths['dir'], 0755, true);
    $file_path = $paths['dir'] . '/' . $file_name;
    
    // Front Matter と 本文の組み立て
    $fm_status = $_POST['status'] ?? 'draft';
    $fm_date = $is_new ? date('Y-m-d H:i:s') : ($_POST['date'] ?? date('Y-m-d H:i:s'));
    $fm_cat = $_POST['category_id'] ?? '';
    
    // 本文にFront Matterが混入していたら除去
    $body = ltrim($content);
    if (str_starts_with($body, '---')) {
        $body = preg_replace('/^---[\s\S]*?---\s*/m', '', $body, 1);
    }

    $md_content = "---\n";
    $md_content .= "id: \"{$id}\"\n";
    $md_content .= "type: \"{$type}\"\n";
    $md_content .= "title: " . json_encode($title, JSON_UNESCAPED_UNICODE) . "\n";
    $md_content .= "slug: " . json_encode($slug, JSON_UNESCAPED_UNICODE) . "\n";
    $md_content .= "date: \"{$fm_date}\"\n";
    $md_content .= "status: \"{$fm_status}\"\n";
    $md_content .= "category: \"{$fm_cat}\"\n";
    $md_content .= "thumbnail: \"{$thumbnail}\"\n";
    $md_content .= "---\n\n";
    $md_content .= $body;
    
    file_put_contents($file_path, $md_content);
    
    // インデックスの更新（file_pathはDATA_DIRの親からの相対パスで保存）
    $site_root = dirname(DATA_DIR);
    $relative_path = ltrim(str_replace(str_replace('\\', '/', $site_root), '', str_replace('\\', '/', $file_path)), '/');
    $post_meta = ['id' => $id, 'type' => $type, 'slug' => $slug, 'title' => $title, 'date' => $fm_date, 'status' => $fm_status, 'category_id' => $fm_cat, 'thumbnail' => $thumbnail, 'file_path' => $relative_path];
    
    if ($is_new) {
        $index_data[] = $post_meta;
    } else {
        foreach ($index_data as &$p) { if ($p['id'] === $id) $p = array_merge($p, $post_meta); }
    }
    
    file_put_contents($paths['index'], json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true, 'id' => $id]);
}

// 削除系アクション（タイプ判定を追加）
if ($action === 'delete') { // 互換性維持
    $id = $_POST['id'] ?? '';
    $type = $_POST['type'] ?? 'post';
    $paths = get_post_paths($type);
    $index_data = file_exists($paths['index']) ? json_decode(file_get_contents($paths['index']), true) : [];
    $filtered = [];
    foreach ($index_data as $p) {
        if ($p['id'] === $id) {
            @unlink($p['file_path']);
        } else {
            $filtered[] = $p;
        }
    }
    file_put_contents($paths['index'], json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'trash') {
    $id = $_POST['id'] ?? '';
    $type = $_POST['type'] ?? 'post';
    $paths = get_post_paths($type);
    $index_data = file_exists($paths['index']) ? json_decode(file_get_contents($paths['index']), true) : [];
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
    file_put_contents($paths['index'], json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'restore') {
    $id = $_POST['id'] ?? '';
    $type = $_POST['type'] ?? 'post';
    $paths = get_post_paths($type);
    $index_data = file_exists($paths['index']) ? json_decode(file_get_contents($paths['index']), true) : [];
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
    file_put_contents($paths['index'], json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'delete_permanently') {
    $id = $_POST['id'] ?? '';
    $type = $_POST['type'] ?? 'post';
    $paths = get_post_paths($type);
    $index_data = file_exists($paths['index']) ? json_decode(file_get_contents($paths['index']), true) : [];
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
    file_put_contents($paths['index'], json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'bulk_trash') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    $type = $_POST['type'] ?? 'post';
    $paths = get_post_paths($type);
    if (!is_array($ids)) json_response(['success' => false, 'message' => 'Invalid IDs']);
    
    $index_data = file_exists($paths['index']) ? json_decode(file_get_contents($paths['index']), true) : [];
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
    file_put_contents($paths['index'], json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'bulk_restore') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    $type = $_POST['type'] ?? 'post';
    $paths = get_post_paths($type);
    if (!is_array($ids)) json_response(['success' => false, 'message' => 'Invalid IDs']);
    
    $index_data = file_exists($paths['index']) ? json_decode(file_get_contents($paths['index']), true) : [];
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
    file_put_contents($paths['index'], json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'bulk_delete_permanently') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    $type = $_POST['type'] ?? 'post';
    $paths = get_post_paths($type);
    if (!is_array($ids)) json_response(['success' => false, 'message' => 'Invalid IDs']);
    
    $index_data = file_exists($paths['index']) ? json_decode(file_get_contents($paths['index']), true) : [];
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
    file_put_contents($paths['index'], json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

// ============================================================
// 単独ページ (Page) アクション
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_page') {
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $thumbnail = $_POST['thumbnail'] ?? '';
    $is_new = empty($id);

    if (empty($title)) json_response(['success' => false, 'message' => 'タイトルを入力してください。']);
    if (empty($slug)) json_response(['success' => false, 'message' => 'スラッグを入力してください。']);
    if (!preg_match('/^[a-zA-Z0-9\-_#]+$/', $slug)) json_response(['success' => false, 'message' => 'スラッグは半角英数字、ハイフン、アンダースコアのみ使用できます。']);

    $pages_index = file_exists(PAGES_INDEX_FILE) ? json_decode(file_get_contents(PAGES_INDEX_FILE), true) : [];
    foreach ($pages_index as $pg) {
        if ($pg['slug'] === $slug && $pg['id'] !== $id) {
            json_response(['success' => false, 'message' => 'このスラッグはすでに使用されています。']);
        }
    }

    if ($is_new) {
        $id = 'page_' . uniqid();
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
    $fm_date = $is_new ? date('Y-m-d H:i:s') : ($_POST['date'] ?? date('Y-m-d H:i:s'));

    // 本文にFront Matterが混入していたら除去
    $body = ltrim($content);
    if (str_starts_with($body, '---')) {
        $body = preg_replace('/^---[\s\S]*?---\s*/m', '', $body, 1);
    }

    $md_content = "---\n";
    $md_content .= "id: \"{$id}\"\n";
    $md_content .= "title: " . json_encode($title, JSON_UNESCAPED_UNICODE) . "\n";
    $md_content .= "slug: " . json_encode($slug, JSON_UNESCAPED_UNICODE) . "\n";
    $md_content .= "date: \"{$fm_date}\"\n";
    $md_content .= "status: \"{$fm_status}\"\n";
    $md_content .= "thumbnail: \"{$thumbnail}\"\n";
    $md_content .= "type: \"page\"\n";
    $md_content .= "---\n\n";
    $md_content .= $body;

    file_put_contents($file_path, $md_content);

    $page_meta = [
        'id' => $id,
        'slug' => $slug,
        'title' => $title,
        'date' => $fm_date,
        'status' => $fm_status,
        'thumbnail' => $thumbnail,
        'file_path' => ltrim(str_replace(str_replace('\\', '/', dirname(DATA_DIR)), '', str_replace('\\', '/', $file_path)), '/'),
        'type' => 'page',
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
    $id = $_POST['id'] ?? '';
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
    $id = $_POST['id'] ?? '';
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
    $id = $_POST['id'] ?? '';
    $pages_index = json_decode(file_exists(PAGES_INDEX_FILE) ? file_get_contents(PAGES_INDEX_FILE) : '[]', true);
    $filtered = [];
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

// ============================================================
// カテゴリ・メディア アクション
// ============================================================

if ($action === 'get_categories') {
    $categories = json_decode(file_exists(CATEGORY_FILE) ? file_get_contents(CATEGORY_FILE) : '[]', true);
    json_response($categories);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_category') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    
    if (empty($name)) json_response(['success' => false, 'message' => 'カテゴリ名を入力してください。']);
    if (empty($slug)) $slug = $name;
    
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

if ($action === 'get_media') {
    $upload_dir = dirname(DATA_DIR) . '/uploads';
    $allowed_ext = ['jpg','jpeg','png','gif','webp','svg'];
    $files = [];
    if (is_dir($upload_dir)) {
        foreach (scandir($upload_dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $fp = $upload_dir . '/' . $file;
            if (is_dir($fp)) continue;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext, true)) continue;
            $files[] = [
                'url' => 'uploads/' . $file,
                'name' => $file,
                'date' => date('Y-m-d H:i', filemtime($fp)),
                'size' => round(filesize($fp) / 1024, 1) . ' KB',
            ];
        }
    }
    usort($files, function($a, $b) { return strcmp($b['date'], $a['date']); });
    json_response($files);
}

// ============================================================
// サイト設定系 アクション
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_settings') {
    $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    if (!is_array($config_data)) $config_data = [];

    $config_data['site_name'] = $_POST['site_name'] ?? '';
    $config_data['site_description'] = $_POST['site_description'] ?? '';
    $config_data['footer_text'] = $_POST['footer_text'] ?? '';
    $config_data['enable_custom_post'] = isset($_POST['enable_custom_post']) ? true : false;
    $config_data['permalink_structure'] = $_POST['permalink_structure'] ?? '/%postname%/';

    $upload_dir = dirname(DATA_DIR) . '/uploads';
    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

    if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION));
        $filename = 'logo_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $upload_dir . '/' . $filename)) {
            $config_data['logo_image_path'] = 'uploads/' . $filename;
        }
    }

    if (isset($_FILES['favicon_image']) && $_FILES['favicon_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['favicon_image']['name'], PATHINFO_EXTENSION));
        $filename = 'favicon_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['favicon_image']['tmp_name'], $upload_dir . '/' . $filename)) {
            $config_data['favicon_image_path'] = 'uploads/' . $filename;
        }
    }

    file_put_contents(CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_cpt_settings') {
    $slug = trim($_POST['cpt_slug'] ?? '');
    $label = trim($_POST['cpt_label'] ?? '');

    if (empty($slug) || empty($label)) {
        json_response(['success' => false, 'message' => 'スラッグと表示名を入力してください。']);
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
        json_response(['success' => false, 'message' => 'スラッグには半角英数字とハイフン、アンダースコアのみ使用可能です。']);
    }

    $cpt_data = file_exists(CPT_CONFIG_FILE) ? json_decode(file_get_contents(CPT_CONFIG_FILE), true) : [];
    if (!is_array($cpt_data)) $cpt_data = [];

    $cpt_data_before = $cpt_data; // 新規登録判定用に書き込み前の状態を保持
    $cpt_data[$slug] = ['label' => $label];
    file_put_contents(CPT_CONFIG_FILE, json_encode($cpt_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // 新規登録の場合のみ投稿ディレクトリを作成
    $is_new_cpt = !isset($cpt_data_before[$slug]);
    if ($is_new_cpt) {
        $cpt_dir = DATA_DIR . "/posts_{$slug}";
        if (!is_dir($cpt_dir)) {
            if (!@mkdir($cpt_dir, 0755, true)) {
                json_response(['success' => false, 'message' => "ディレクトリの作成に失敗しました: {$cpt_dir}"]);
            }
        }
    }

    json_response(['success' => true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_cpt_settings') {
    $slug = trim($_POST['cpt_slug'] ?? '');
    $cpt_data = file_exists(CPT_CONFIG_FILE) ? json_decode(file_get_contents(CPT_CONFIG_FILE), true) : [];
    if (!is_array($cpt_data)) $cpt_data = [];

    if (isset($cpt_data[$slug])) {
        unset($cpt_data[$slug]);
        file_put_contents(CPT_CONFIG_FILE, json_encode($cpt_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    json_response(['success' => true]);
}

// ============================================================
// メニュー アクション
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_menus') {
    $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    if (!is_array($config_data)) $config_data = [];

    $posted_groups = $_POST['menu_groups'] ?? [];
    if (!isset($config_data['menus'])) $config_data['menus'] = [];

    foreach ($posted_groups as $gid => $group) {
        $name = trim($group['name'] ?? '');
        if (empty($name)) continue;

        $items = [];
        foreach ($group['items'] ?? [] as $item) {
            $label = trim($item['label'] ?? '');
            $url   = trim($item['url'] ?? '');
            if ($label !== '' || $url !== '') {
                $items[] = ['label' => $label, 'url' => $url];
            }
        }

        $config_data['menus'][$gid] = [
            'name'  => $name,
            'items' => $items,
        ];
    }

    $all_positions = ['header','header_mobile','header_mobile_btn','footer','footer_mobile_btn','mobile_slide'];
    $active_gid = $_POST['active_gid'] ?? '';

    // [] (配列) で保存されていた場合も {} (連想配列) に強制修正
    if (!isset($config_data['menu_locations']) || !is_array($config_data['menu_locations']) || array_values($config_data['menu_locations']) === $config_data['menu_locations']) {
        $config_data['menu_locations'] = (object)[];
    }
    $config_data['menu_locations'] = (array)$config_data['menu_locations'];

    foreach ($all_positions as $pos) {
        if (isset($config_data['menu_locations'][$pos]) && $config_data['menu_locations'][$pos] === $active_gid) {
            unset($config_data['menu_locations'][$pos]);
        }
    }
    foreach ($_POST['menu_locations'] ?? [] as $pos => $gid) {
        if (in_array($pos, $all_positions, true)) {
            $config_data['menu_locations'][$pos] = $gid;
        }
    }

    file_put_contents(CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

// ============================================================
// ウィジェット アクション
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_widgets') {
    $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    if (!is_array($config_data)) $config_data = [];

    $posted_widgets = $_POST['widgets'] ?? [];
    $cleaned = [];

    foreach ($posted_widgets as $area_id => $items) {
        if (!is_array($items)) continue;
        $cleaned[$area_id] = [];
        foreach ($items as $item) {
            if (empty($item['type'])) continue;
            $cleaned[$area_id][] = $item;
        }
    }

    $config_data['widgets'] = $cleaned;
    file_put_contents(CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

json_response(['error' => 'Bad Request', 'message' => '不明なアクションです。'], 400);