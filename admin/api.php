<?php
// admin/api.php
require_once __DIR__ . '/config.php';
session_start();

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// 認証処理の修正: site_config.json の情報と照合する
if (!isset($_SESSION['raptio_auth']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 設定ファイルの読み込み
    $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    
    // ユーザー名とパスワード(ハッシュ)の検証
    if (!empty($config_data['username']) && 
        $username === $config_data['username'] && 
        password_verify($password, $config_data['password'])) {
        
        $_SESSION['raptio_auth'] = true;
        header('Location: index.php');
        exit;
    } else {
        header('Location: index.php?error=1');
        exit;
    }
}

// セキュリティガード
if (!isset($_SESSION['raptio_auth'])) {
    json_response(['error' => 'Unauthorized'], 401);
}

$action = $_REQUEST['action'] ?? '';

// --- 以降は既存のロジック（そのまま維持）---

// メニュー保存アクション
if ($action === 'save_menus') {
    $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    
    $existing_menus = $config_data['menus'] ?? [];
    $new_groups = $_POST['menu_groups'] ?? [];
    foreach ($new_groups as $gid => $group_data) {
        $existing_menus[$gid] = $group_data;
    }
    $config_data['menus'] = $existing_menus;
    
    $existing_locations = $config_data['menu_locations'] ?? [];
    $new_locations = $_POST['menu_locations'] ?? [];
    $config_data['menu_locations'] = array_merge($existing_locations, $new_locations);
    
    if (file_put_contents(CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        json_response(['error' => 'メニューの保存に失敗しました'], 500);
    }
    json_response(['success' => true]);
}

// メニュー削除アクション
if ($action === 'delete_menu') {
    $gid = $_POST['gid'] ?? '';
    $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    
    if (isset($config_data['menus'][$gid])) {
        unset($config_data['menus'][$gid]);
        
        if (isset($config_data['menu_locations'])) {
            foreach ($config_data['menu_locations'] as $loc => $assigned_gid) {
                if ($assigned_gid === $gid) {
                    $config_data['menu_locations'][$loc] = "";
                }
            }
        }
        
        file_put_contents(CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        json_response(['success' => true]);
    }
    json_response(['error' => 'メニューが見つかりません'], 404);
}

// 共通設定の保存アクション
if ($action === 'save_settings') {
    $settings = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];

    $logo_image_path = $settings['logo_image_path'] ?? '';
    $favicon_image_path = $settings['favicon_image_path'] ?? '';

    if (isset($_POST['delete_logo_image']) && $_POST['delete_logo_image'] === '1') {
        if ($logo_image_path && file_exists(__DIR__ . '/../' . $logo_image_path)) {
            @unlink(__DIR__ . '/../' . $logo_image_path);
        }
        $logo_image_path = '';
    }

    if (isset($_POST['delete_favicon_image']) && $_POST['delete_favicon_image'] === '1') {
        if ($favicon_image_path && file_exists(__DIR__ . '/../' . $favicon_image_path)) {
            @unlink(__DIR__ . '/../' . $favicon_image_path);
        }
        $favicon_image_path = '';
    }

    if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['logo_image']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) {
            if ($logo_image_path && file_exists(__DIR__ . '/../' . $logo_image_path)) @unlink(__DIR__ . '/../' . $logo_image_path);
            $new_filename = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($file_tmp, __DIR__ . '/../uploads/' . $new_filename)) $logo_image_path = 'uploads/' . $new_filename;
        }
    }

    if (isset($_FILES['favicon_image']) && $_FILES['favicon_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['favicon_image']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['favicon_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'ico', 'webp', 'svg'])) {
            if ($favicon_image_path && file_exists(__DIR__ . '/../' . $favicon_image_path)) @unlink(__DIR__ . '/../' . $favicon_image_path);
            $new_filename = 'favicon_' . time() . '.' . $ext;
            if (move_uploaded_file($file_tmp, __DIR__ . '/../uploads/' . $new_filename)) $favicon_image_path = 'uploads/' . $new_filename;
        }
    }

    $fields = ['site_name', 'site_description', 'sidebar_title', 'sidebar_content', 'footer_text'];
    foreach ($fields as $key) {
        if (isset($_POST[$key])) {
            $settings[$key] = trim($_POST[$key]);
        }
    }
    
    $settings['logo_image_path'] = $logo_image_path;
    $settings['favicon_image_path'] = $favicon_image_path;

    if (file_put_contents(CONFIG_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        json_response(['error' => '設定の保存に失敗しました'], 500);
    }
    RaptioPlugin::do_action('raptio_after_save_settings', $settings);
    json_response(['success' => true]);
}

// ウィジェット保存アクション
if ($action === 'save_widgets') {
    $config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
    $config_data['widgets'] = $_POST['widgets'] ?? [];
    
    if (file_put_contents(CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        json_response(['error' => 'ウィジェットの保存に失敗しました'], 500);
    }
    json_response(['success' => true]);
}

// 記事保存
if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $category_id = $_POST['category_id'] ?? '';
    
    $thumbnail = $_POST['thumbnail'] ?? '';
    if (isset($_FILES['thumb_file']) && $_FILES['thumb_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['thumb_file']['name'], PATHINFO_EXTENSION));
        $new_name = 'thumb_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['thumb_file']['tmp_name'], __DIR__ . '/../uploads/' . $new_name)) {
            $thumbnail = 'uploads/' . $new_name;
        }
    }

    if (!$title || !$slug) {
        json_response(['error' => 'タイトルとスラッグは必須です'], 400);
    }

    $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
    
    if (!$id) {
        $id = bin2hex(random_bytes(4));
        $is_new = true;
    } else {
        $is_new = false;
    }

    $file_name = $slug . '.md';
    $file_path = 'data/posts/' . $file_name;
    $full_file_path = POSTS_DIR . '/' . $file_name;

    if (file_put_contents($full_file_path, $content) === false) {
        json_response(['error' => 'ファイルの書き込みに失敗しました'], 500);
    }

    $index_content = file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]';
    $index_data = json_decode($index_content, true) ?? [];
    
    $post_meta = [
        'id' => $id,
        'slug' => $slug,
        'title' => $title,
        'date' => $is_new ? date('Y-m-d H:i:s') : ($_POST['date'] ?? date('Y-m-d H:i:s')),
        'status' => $status,
        'category_id' => $category_id,
        'thumbnail' => $thumbnail,
        'file_path' => $file_path
    ];

    if ($is_new) {
        $index_data[] = $post_meta;
    } else {
        foreach ($index_data as &$post) {
            if ($post['id'] === $id) {
                if ($post['slug'] !== $slug) {
                    @unlink(DATA_DIR . '/../' . $post['file_path']);
                }
                $post = array_merge($post, $post_meta);
                break;
            }
        }
    }

    usort($index_data, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    file_put_contents(INDEX_FILE, json_encode($index_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    RaptioPlugin::do_action('raptio_after_save', $post_meta);
    json_response(['success' => true, 'id' => $id]);
}

// 記事削除
if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if (!$id) json_response(['error' => 'IDが指定されていません'], 400);

    $index_content = file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]';
    $index_data = json_decode($index_content, true) ?? [];
    $filtered_index = [];
    
    foreach ($index_data as $post) {
        if ($post['id'] === $id) {
            @unlink(DATA_DIR . '/../' . $post['file_path']);
            if (!empty($post['thumbnail']) && file_exists(__DIR__ . '/../' . $post['thumbnail'])) {
                @unlink(__DIR__ . '/../' . $post['thumbnail']);
            }
            RaptioPlugin::do_action('raptio_after_delete', $post);
        } else {
            $filtered_index[] = $post;
        }
    }

    file_put_contents(INDEX_FILE, json_encode($filtered_index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_response(['success' => true]);
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}