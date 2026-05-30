<?php
// admin/themes.php
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }

$page_title = 'テーマ管理';
$current_page = 'themes';

// テーマ保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['active_theme'])) {
    $config = json_decode(file_get_contents(CONFIG_FILE), true);
    $config['active_theme'] = $_POST['active_theme'];
    file_put_contents(CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $message = 'テーマを更新しました。';
}

$config = json_decode(file_get_contents(CONFIG_FILE), true);
$current_theme = $config['active_theme'] ?? 'lux';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="wp-content-title-area">
    <h2>テーマ管理</h2>
</div>

<?php if (isset($message)): ?>
    <div style="padding: 10px; background: #e0f2f1; margin-bottom: 20px;"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST" class="settings-card">
    <h3>現在のテーマ: <?php echo htmlspecialchars($current_theme); ?></h3>
    <div style="margin-bottom: 20px;">
        <label>テーマを選択:</label>
        <select name="active_theme" style="max-width: 300px;">
            <?php
            $themes_dir = __DIR__ . '/../themes/';
            $themes = array_filter(glob($themes_dir . '*'), 'is_dir');
            foreach ($themes as $theme_path) {
                $theme_name = basename($theme_path);
                $selected = ($current_theme === $theme_name) ? 'selected' : '';
                echo "<option value='{$theme_name}' {$selected}>{$theme_name}</option>";
            }
            ?>
        </select>
    </div>
    <button type="submit" class="button button-primary">テーマを適用</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>