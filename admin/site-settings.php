<?php
// admin/site-settings.php
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }

$config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];

$site_name = $config_data['site_name'] ?? 'Parallel';
$site_description = $config_data['site_description'] ?? '';
$footer_text = $config_data['footer_text'] ?? 'Parallel Project. All rights reserved.';
$logo_image_path = $config_data['logo_image_path'] ?? '';
$favicon_image_path = $config_data['favicon_image_path'] ?? '';

$page_title = '基本設定';
$current_page = 'settings';
$sub_page = 'basic';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="wp-admin-header">
    <h2>基本設定</h2>
</div>

<div class="settings-container" style="max-width: 600px;">
    <form id="settingsForm">
        <input type="hidden" name="action" value="save_settings">
        
        <div class="settings-card">
            <h3>サイト情報</h3>
            <div class="form-group">
                <label>サイト名</label>
                <input type="text" name="site_name" id="site_name" value="<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>" required style="width:100%;">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>キャッチコピー / 説明文</label>
                <input type="text" name="site_description" id="site_description" value="<?php echo htmlspecialchars($site_description, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>フッター表記</label>
                <input type="text" name="footer_text" id="footer_text" value="<?php echo htmlspecialchars($footer_text, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;">
            </div>
        </div>

        <div class="settings-card" style="margin-top:20px;">
            <h3>ロゴ ＆ ファビコン</h3>
            <div class="form-group">
                <label>サイトロゴ</label>
                <?php if ($logo_image_path && file_exists(__DIR__ . '/../' . $logo_image_path)): ?>
                    <div style="margin-bottom:10px;"><img src="../<?php echo htmlspecialchars($logo_image_path, ENT_QUOTES, 'UTF-8'); ?>" style="max-height:40px; background:#0a0a0f; padding:5px; border-radius:4px;"></div>
                <?php endif; ?>
                <input type="file" name="logo_image" accept="image/*">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>ファビコン</label>
                <?php if ($favicon_image_path && file_exists(__DIR__ . '/../' . $favicon_image_path)): ?>
                    <div style="margin-bottom:10px;"><img src="../<?php echo htmlspecialchars($favicon_image_path, ENT_QUOTES, 'UTF-8'); ?>" style="max-height:32px; background:#0a0a0f; padding:5px; border-radius:4px;"></div>
                <?php endif; ?>
                <input type="file" name="favicon_image" accept="image/*">
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="button button-primary" style="padding:10px 30px;">変更を保存</button>
        </div>
    </form>
</div>

<script>
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { 
            if (data.success) { 
                alert('基本設定を保存しました！'); 
                window.location.reload();
            } else { 
                alert('保存に失敗しました: ' + data.error); 
            } 
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>