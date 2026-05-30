<?php
// admin/site-menu.php
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }

$config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
$menus = $config_data['menus'] ?? [];
$locations = $config_data['menu_locations'] ?? [];

$positions = [
    'header' => 'ヘッダーメニュー',
    'header_mobile' => 'ヘッダーモバイルメニュー',
    'header_mobile_btn' => 'ヘッダーモバイルボタン',
    'footer' => 'フッターメニュー',
    'footer_mobile_btn' => 'フッターモバイルボタン',
    'mobile_slide' => 'モバイルスライドインメニュー'
];

$current_gid = $_GET['gid'] ?? (key($menus) ?: '');
$current_group = $menus[$current_gid] ?? ['name' => '新規メニュー', 'items' => []];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="wp-admin-header"><h2>メニュー管理</h2></div>

<div style="display: flex; gap: 20px; align-items: flex-start; margin-top: 20px;">
    <div style="width: 250px; background: #fff; border: 1px solid #c3c4c7; padding: 15px;">
        <h3 style="margin-bottom: 15px; font-size: 14px;">メニューセット</h3>
        <ul style="list-style: none; margin: 0; padding: 0;">
            <?php foreach ($menus as $gid => $group): ?>
            <li style="margin-bottom: 8px;">
                <a href="?gid=<?php echo htmlspecialchars($gid); ?>" style="display: block; padding: 8px 12px; border: 1px solid #c3c4c7; background: <?php echo ($gid === $current_gid) ? '#2271b1' : '#f6f7f7'; ?>; color: <?php echo ($gid === $current_gid) ? '#fff' : '#3c434a'; ?>; text-decoration: none; font-weight: 600;">
                    <?php echo htmlspecialchars($group['name']); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" onclick="createNewMenu()" style="width: 100%; margin-top: 15px; padding: 8px; cursor: pointer;">+ 新規作成</button>
    </div>

    <div style="flex: 1; background: #fff; border: 1px solid #c3c4c7; padding: 20px;">
        <form id="menuForm">
            <input type="hidden" name="action" value="save_menus">
            <input type="hidden" name="active_gid" value="<?php echo htmlspecialchars($current_gid); ?>">
            
            <?php if($current_gid): ?>
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold;">メニュー名:</label>
                <input type="text" name="menu_groups[<?php echo htmlspecialchars($current_gid); ?>][name]" value="<?php echo htmlspecialchars($current_group['name']); ?>" style="width: 100%; padding: 8px; margin-top: 5px;">
            </div>

            <div id="items-list" style="margin-bottom: 20px;">
                <?php foreach (($current_group['items'] ?? []) as $idx => $item): ?>
                <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                    <input type="text" name="menu_groups[<?php echo htmlspecialchars($current_gid); ?>][items][<?php echo $idx; ?>][label]" value="<?php echo htmlspecialchars($item['label']); ?>" placeholder="ラベル" style="flex: 1; padding: 6px;">
                    <input type="text" name="menu_groups[<?php echo htmlspecialchars($current_gid); ?>][items][<?php echo $idx; ?>][url]" value="<?php echo htmlspecialchars($item['url']); ?>" placeholder="URL" style="flex: 1; padding: 6px;">
                    <button type="button" onclick="this.parentElement.remove()" style="padding: 0 10px;">削除</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addItem()" style="margin-bottom: 30px; padding: 8px 15px;">+ 項目追加</button>
            <?php endif; ?>

            <div style="border-top: 1px solid #c3c4c7; padding-top: 20px;">
                <h3 style="margin-bottom: 15px;">メニューの位置</h3>
                <div style="display: grid; grid-template-columns: 1fr; gap: 10px;">
                    <?php foreach ($positions as $pos_key => $pos_label): ?>
                    <label style="display: flex; align-items: center; gap: 10px; padding: 8px; border: 1px solid #eee; cursor: pointer;">
                        <input type="checkbox" name="menu_locations[<?php echo $pos_key; ?>]" value="<?php echo htmlspecialchars($current_gid); ?>" 
                        <?php echo (isset($locations[$pos_key]) && $locations[$pos_key] === $current_gid) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($pos_label); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" style="margin-top: 30px; padding: 10px 25px; background: #2271b1; color: #fff; border: none; cursor: pointer; font-weight: bold;">変更を保存</button>
        </form>
    </div>
</div>

<script>
    function addItem() {
        const list = document.getElementById('items-list');
        const idx = Date.now();
        const gid = document.querySelector('input[name="active_gid"]').value;
        list.insertAdjacentHTML('beforeend', `<div style="display: flex; gap: 10px; margin-bottom: 8px;"><input type="text" name="menu_groups[${gid}][items][${idx}][label]" placeholder="ラベル" style="flex: 1; padding: 6px;"><input type="text" name="menu_groups[${gid}][items][${idx}][url]" placeholder="URL" style="flex: 1; padding: 6px;"><button type="button" onclick="this.parentElement.remove()" style="padding: 0 10px;">削除</button></div>`);
    }
    function createNewMenu() {
        const name = prompt("メニュー名を入力:");
        if (name) {
            const gid = 'm_' + Date.now();
            const formData = new FormData();
            formData.append('action', 'save_menus');
            formData.append(`menu_groups[${gid}][name]`, name);
            fetch('api.php', { method: 'POST', body: formData }).then(() => location.href = `?gid=${gid}`);
        }
    }
    document.getElementById('menuForm').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api.php', { method: 'POST', body: new FormData(this) }).then(() => { alert('保存しました'); location.reload(); });
    });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>