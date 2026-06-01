<?php
// admin/themes.php
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }

// テーマの style.css から全情報を抽出する関数
function get_theme_metadata($theme_path) {
    $style_file = $theme_path . '/style.css';
    $meta = [
        'name' => basename($theme_path),
        'theme_uri' => '',
        'author' => 'Unknown',
        'author_uri' => '',
        'description' => '説明なし',
        'version' => '1.0.0',
        'license' => '',
        'license_uri' => '',
        'tags' => ''
    ];

    if (file_exists($style_file)) {
        $content = file_get_contents($style_file);
        $patterns = [
            'name'        => '/Theme Name:\s*(.*)/i',
            'theme_uri'   => '/Theme URI:\s*(.*)/i',
            'author'      => '/Author:\s*(.*)/i',
            'author_uri'  => '/Author URI:\s*(.*)/i',
            'description' => '/Description:\s*(.*)/i',
            'version'     => '/Version:\s*(.*)/i',
            'license'     => '/License:\s*(.*)/i',
            'license_uri' => '/License URI:\s*(.*)/i',
            'tags'        => '/Tags:\s*(.*)/i',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $meta[$key] = trim($matches[1]);
            }
        }
    }
    return $meta;
}

// 設定ファイルの読み込み
$config = json_decode(file_get_contents(CONFIG_FILE), true);

// テーマ変更処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['active_theme'])) {
    $config['active_theme'] = $_POST['active_theme'];
    file_put_contents(CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    // リダイレクトして画面を更新する
    header('Location: themes.php?status=updated');
    exit;
}

$current_theme = $config['active_theme'] ?? 'lux';
$current_page = 'themes';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="wp-content-title-area"><h2>テーマ管理</h2></div>

<?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
    <div style="padding: 10px; background: #e0f2f1; margin-bottom: 20px; border-left: 4px solid #008a74;">テーマ設定を更新しました。</div>
<?php endif; ?>

<div class="theme-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
    <?php
    $themes = array_filter(glob(__DIR__ . '/../themes/*', GLOB_ONLYDIR));
    foreach ($themes as $theme_path) {
        $theme_name = basename($theme_path);
        $meta = get_theme_metadata($theme_path);
        $is_active = ($current_theme === $theme_name);
        $img = file_exists($theme_path . '/screenshot.png') ? '../themes/'.$theme_name.'/screenshot.png' : '../themes/'.$theme_name.'/screenshot.jpg';
        ?>
        
        <div class="theme-card <?php echo $is_active ? 'is-active' : ''; ?>" style="border: 1px solid #ccd0d4; background:#fff; position:relative;">
            <div class="theme-screenshot" style="position:relative; height:180px; overflow:hidden; background:#f6f7f7;">
                <img src="<?php echo $img; ?>" style="width:100%; height:100%; object-fit:cover;">
                <div class="theme-actions" style="position:absolute; inset:0; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center;">
                    <button type="button" class="button" onclick="document.getElementById('modal-<?php echo $theme_name; ?>').style.display='flex'">テーマの詳細</button>
                </div>
            </div>
            <div style="padding: 10px;">
                <h3 style="font-size: 14px; margin: 0;"><?php echo htmlspecialchars($meta['name']); ?></h3>
                <?php if ($is_active): ?><span style="color: #2271b1; font-size: 11px; font-weight: bold;">■ 有効中</span><?php endif; ?>
            </div>
            <style>
                .theme-card:hover .theme-actions { display: flex !important; }
            </style>
        </div>

        <div id="modal-<?php echo $theme_name; ?>" class="theme-modal-overlay" onclick="this.style.display='none'" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div class="theme-modal" onclick="event.stopPropagation()" style="background: #fff; width: 800px; max-width: 90%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 5px 15px rgba(0,0,0,0.3); overflow: hidden;">
                <div class="modal-header" style="padding: 15px 20px; border-bottom: 1px solid #dcdcde; font-size: 16px; font-weight: 600;">詳細情報: <?php echo htmlspecialchars($meta['name']); ?></div>
                <div class="modal-body" style="padding: 20px; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: row;">
                    <div style="width: 300px; flex-shrink: 0;">
                        <img src="<?php echo $img; ?>" style="width: 100%; border: 1px solid #ddd;">
                    </div>
                    <div style="flex: 1; padding-left: 20px;">
                        <h2 style="margin-top: 0;"><?php echo htmlspecialchars($meta['name']); ?> <small style="font-weight: normal; font-size: 14px;">v<?php echo htmlspecialchars($meta['version']); ?></small></h2>
                        <p><?php echo nl2br(htmlspecialchars($meta['description'])); ?></p>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px;">
                            <?php if ($meta['author']): ?>
                            <tr><th style="text-align: left; padding: 5px; color: #666; width: 100px;">作者:</th><td style="padding: 5px;"><a href="<?php echo htmlspecialchars($meta['author_uri']); ?>" target="_blank"><?php echo htmlspecialchars($meta['author']); ?></a></td></tr>
                            <?php endif; ?>
                            <?php if ($meta['theme_uri']): ?>
                            <tr><th style="text-align: left; padding: 5px; color: #666;">テーマサイト:</th><td style="padding: 5px;"><a href="<?php echo htmlspecialchars($meta['theme_uri']); ?>" target="_blank"><?php echo htmlspecialchars($meta['theme_uri']); ?></a></td></tr>
                            <?php endif; ?>
                            <?php if ($meta['license']): ?>
                            <tr><th style="text-align: left; padding: 5px; color: #666;">ライセンス:</th><td style="padding: 5px;"><a href="<?php echo htmlspecialchars($meta['license_uri']); ?>" target="_blank"><?php echo htmlspecialchars($meta['license']); ?></a></td></tr>
                            <?php endif; ?>
                            <?php if ($meta['tags']): ?>
                            <tr><th style="text-align: left; padding: 5px; color: #666;">タグ:</th><td style="padding: 5px;"><?php echo htmlspecialchars($meta['tags']); ?></td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 15px 20px; border-top: 1px solid #dcdcde; background: #f6f7f7; display: flex; justify-content: space-between;">
                    <div>
                        <?php if (!$is_active): ?>
                            <form method="POST"><input type="hidden" name="active_theme" value="<?php echo htmlspecialchars($theme_name); ?>"><button type="submit" class="button button-primary">有効化</button></form>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button" onclick="document.getElementById('modal-<?php echo $theme_name; ?>').style.display='none'">閉じる</button>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>