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
        $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
        if (!empty($config_data['username']) && $username === $config_data['username'] && password_verify($password, $config_data['password'])) {
            $_SESSION['raptio_auth'] = true;
            header('Location: index.php');
            exit;
        }
        header('Location: index.php?error=1');
        exit;
    }
    json_response(['error' => 'Unauthorized'], 401);
}

$action = $_REQUEST['action'] ?? '';

// --- 各アクション処理 ---

if ($action === 'save_menus') {
    $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    $config_data['menus'] = $_POST['menu_groups'] ?? [];
    $config_data['menu_locations'] = array_merge($config_data['menu_locations'] ?? [], $_POST['menu_locations'] ?? []);
    file_put_contents(CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'delete_menu') {
    $gid = $_POST['gid'] ?? '';
    $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    if (isset($config_data['menus'][$gid])) {
        unset($config_data['menus'][$gid]);
        file_put_contents(CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        json_response(['success' => true]);
    }
    json_response(['error' => 'メニューが見つかりません'], 404);
}

if ($action === 'save_settings') {
    $settings = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    $fields = ['site_name', 'site_description', 'sidebar_title', 'sidebar_content', 'footer_text'];
    foreach ($fields as $key) { if (isset($_POST[$key])) $settings[$key] = trim($_POST[$key]); }
    file_put_contents(CONFIG_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'save_widgets') {
    $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    $config_data['widgets'] = $_POST['widgets'] ?? [];
    file_put_contents(CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

// 記事保存
if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    
    if (!$title || !$slug) json_response(['error' => 'タイトルとスラッグは必須です'], 400);

    $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
    $is_new = empty($id);
    if ($is_new) $id = bin2hex(random_bytes(4));

    $thumbnail = $_POST['thumbnail'] ?? '';
    if (isset($_FILES['thumb_file']) && $_FILES['thumb_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['thumb_file']['name'], PATHINFO_EXTENSION));
        $new_name = 'thumb_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['thumb_file']['tmp_name'], __DIR__ . '/../uploads/' . $new_name)) {
            $thumbnail = 'uploads/' . $new_name;
        }
    }

    $file_path = 'data/posts/' . $slug . '.md';
    if (file_put_contents(POSTS_DIR . '/' . $slug . '.md', $content) === false) {
        json_response(['error' => '書き込み失敗'], 500);
    }

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

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true);
    $filtered = [];
    foreach ($index_data as $p) {
        if ($p['id'] === $id) {
            @unlink(DATA_DIR . '/../' . $p['file_path']);
            if (!empty($p['thumbnail'])) @unlink(__DIR__ . '/../' . $p['thumbnail']);
        } else {
            $filtered[] = $p;
        }
    }
    file_put_contents(INDEX_FILE, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
}

// バッファ終了
if (ob_get_length()) ob_end_flush();