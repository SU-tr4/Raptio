<?php
// admin/plugins.php
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/config.php';

/**
 * プラグインヘッダー取得
 */
function get_plugin_headers($file_path) {
    if (!file_exists($file_path)) return [];
    $content = file_get_contents($file_path);
    $headers = [
        'name'        => 'Plugin Name',
        'description' => 'Description',
        'version'     => 'Version',
        'author'      => 'Author',
    ];
    $data = [];
    foreach ($headers as $key => $label) {
        if (preg_match('/' . preg_quote($label) . ':\s*(.*)$/mi', $content, $matches)) {
            $data[$key] = trim($matches[1]);
        }
    }
    return $data;
}

/**
 * プラグインデータの取得
 */
function get_plugins_data() {
    if (!file_exists(PLUGINS_JSON)) return [];
    $json = file_get_contents(PLUGINS_JSON);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = get_plugins_data();
    $needs_save = false;

    // 1. 一括操作
    if (isset($_POST['do_bulk_action']) && !empty($_POST['bulk_action'])) {
        $action = $_POST['bulk_action']; 
        $targets = $_POST['checked_plugins'] ?? [];
        
        foreach ($targets as $folder) {
            if (isset($data[$folder])) {
                if ($action === 'activate') {
                    $data[$folder]['active'] = true;
                } else if ($action === 'deactivate') {
                    $data[$folder]['active'] = false;
                }
                $needs_save = true;
            }
        }
    }
    // 2. 個別操作
    else {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'toggle_plugin_') === 0) {
                $target = str_replace('toggle_plugin_', '', $key);
                if (isset($data[$target])) {
                    $data[$target]['active'] = !($data[$target]['active'] ?? false);
                    $needs_save = true;
                }
                break;
            }
        }
    }

    if ($needs_save) {
        file_put_contents(PLUGINS_JSON, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header('Location: plugins.php' . (isset($_GET['status']) ? '?status=' . htmlspecialchars($_GET['status']) : ''));
        exit;
    }

    // ZIPインストール
    if (isset($_FILES['plugin_zip']) && $_FILES['plugin_zip']['error'] === UPLOAD_ERR_OK) {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($_FILES['plugin_zip']['tmp_name']) === TRUE) {
                $zip->extractTo(PLUGINS_DIR);
                $zip->close();
                header('Location: plugins.php');
                exit;
            }
        }
    }
}

// データ同期処理（バグ修正・自動同期強化版）
$plugins_data = get_plugins_data();
$plugin_dirs = array_filter(glob(PLUGINS_DIR . '/*', GLOB_ONLYDIR), 'is_dir');
$current_folders = array_map('basename', $plugin_dirs);
$needs_save = false;

// 1. 既に削除されたプラグインフォルダをJSONから除去
foreach ($plugins_data as $folder_name => $meta) {
    if (!in_array($folder_name, $current_folders)) { 
        unset($plugins_data[$folder_name]); 
        $needs_save = true; 
    }
}

// 2. 存在する全フォルダのメタデータをPHPファイルから常に最新化して同期
foreach ($current_folders as $folder_name) {
    // 現在の有効化状態を維持（未登録なら初期値はfalse）
    $is_active = (bool)($plugins_data[$folder_name]['active'] ?? false);
    
    // ベースとなる初期メタデータ構造を定義
    $meta = [
        'name'        => $folder_name,
        'active'      => $is_active,
        'description' => '-',
        'version'     => '-',
        'author'      => '-'
    ];

    // フォルダ内のPHPファイルを走査してヘッダーコメントを抽出・マージ
    $files = glob(PLUGINS_DIR . '/' . $folder_name . '/*.php');
    foreach ($files as $file) {
        $h = get_plugin_headers($file);
        if (!empty($h)) { 
            $meta = array_merge($meta, $h); 
            break; // 有効なヘッダーを持つメインファイルが見つかればループを抜ける
        }
    }

    // JSON側の既存データに不足がある、または変更の差分がある場合のみ更新
    if (!isset($plugins_data[$folder_name]) || $plugins_data[$folder_name] !== $meta) {
        $plugins_data[$folder_name] = $meta;
        $needs_save = true;
    }
}

// 差分があればJSONへ保存
if ($needs_save) {
    file_put_contents(PLUGINS_JSON, json_encode($plugins_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// フィルター処理
$status = $_GET['status'] ?? 'all';
$counts = ['all' => count($plugins_data), 'active' => 0, 'inactive' => 0];
$filtered_plugins = [];

foreach ($plugins_data as $folder_name => $data) {
    $is_active = (bool)($data['active'] ?? false);
    if ($is_active) $counts['active']++;
    else $counts['inactive']++;

    if ($status === 'active' && !$is_active) continue;
    if ($status === 'inactive' && $is_active) continue;
    $filtered_plugins[$folder_name] = $data;
}
$current_page = 'plugins';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>


<div class="wp-content-title-area">
    <h2>プラグイン</h2>
    <button type="button" class="toggle-btn" onclick="document.getElementById('upload-card').style.display = (document.getElementById('upload-card').style.display === 'block') ? 'none' : 'block';">＋ 新規プラグインを追加</button>
</div>

<div id="upload-card" class="plugin-upload-card">
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="plugin_zip" accept=".zip" required>
        <button type="submit" class="button button-primary">インストール</button>
    </form>
</div>

<div class="filter-links">
    <a href="?status=all" class="<?php echo $status === 'all' ? 'current' : ''; ?>">すべて<span>(<?php echo $counts['all']; ?>)</span></a> |
    <a href="?status=active" class="<?php echo $status === 'active' ? 'current' : ''; ?>">使用中<span>(<?php echo $counts['active']; ?>)</span></a> |
    <a href="?status=inactive" class="<?php echo $status === 'inactive' ? 'current' : ''; ?>">停止中<span>(<?php echo $counts['inactive']; ?>)</span></a>
</div>

<form method="POST">
    <div class="tablenav">
        <div class="tablenav-left">
            <select name="bulk_action">
                <option value="">一括操作</option>
                <option value="activate">有効化</option>
                <option value="deactivate">停止</option>
            </select>
            <button type="submit" name="do_bulk_action" value="1" class="button">適用</button>
        </div>
        <div class="tablenav-right">
            <input type="text" id="plugin-search" placeholder="プラグインを検索...">
        </div>
    </div>

    <table class="wp-list-table">
        <thead>
            <tr>
                <th style="width: 30px;"><input type="checkbox" id="select-all"></th>
                <th style="width: 25%;">プラグイン</th>
                <th style="width: 100px;">状態</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filtered_plugins as $folder_name => $data): 
                $is_active = (bool)($data['active'] ?? false);
            ?>
            <tr class="plugin-row">
                <td><input type="checkbox" name="checked_plugins[]" value="<?php echo htmlspecialchars($folder_name); ?>"></td>
                <td>
                    <div class="plugin-title"><?php echo htmlspecialchars($data['name'] ?? $folder_name); ?></div>
                    <div style="margin-top: 5px;">
                        <button type="submit" name="toggle_plugin_<?php echo htmlspecialchars($folder_name); ?>" class="action-btn <?php echo $is_active ? 'btn-stop' : 'btn-activate'; ?>">
                            <?php echo $is_active ? '停止' : '有効化'; ?>
                        </button>
                    </div>
                </td>
                <td>
                    <span class="status-badge <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $is_active ? '有効化' : '停止'; ?>
                    </span>
                </td>
                <td>
                    <div><?php echo htmlspecialchars($data['description'] ?? '-'); ?></div>
                    <div style="font-size: 11px; color: #666; margin-top: 5px;">
                        バージョン: <?php echo htmlspecialchars($data['version'] ?? '-'); ?> | 
                        作者: <?php echo htmlspecialchars($data['author'] ?? '-'); ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</form>

<script>
document.getElementById('plugin-search').addEventListener('keyup', function() {
    let term = this.value.toLowerCase();
    document.querySelectorAll('.plugin-row').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
    });
});
document.getElementById('select-all').addEventListener('change', function() {
    document.querySelectorAll('input[name="checked_plugins[]"]').forEach(cb => cb.checked = this.checked);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>