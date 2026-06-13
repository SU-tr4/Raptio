<?php
/**
 * admin/site-settings.php
 * サイトの基本設定およびパーマリンク設定
 */
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }

if (!defined('CONFIG_FILE')) {
    define('CONFIG_FILE', __DIR__ . '/../data/site_config.json');
}

$config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];

$site_name           = $config_data['site_name']           ?? 'Parallel';
$site_description    = $config_data['site_description']    ?? '';
$footer_text         = $config_data['footer_text']         ?? 'Parallel Project. All rights reserved.';
$logo_image_path     = $config_data['logo_image_path']     ?? '';
$favicon_image_path  = $config_data['favicon_image_path']  ?? '';
$enable_custom_post  = $config_data['enable_custom_post']  ?? false;
$permalink_structure = $config_data['permalink_structure'] ?? '/%postname%';

// カスタム構造かどうか判定
$preset_values = ['plain', '/%year%/%monthnum%/%day%/%postname%/', '/%year%/%monthnum%/%postname%/', '/archives/%post_id%', '/%postname%/'];
$is_custom     = !in_array($permalink_structure, $preset_values, true);
$custom_value  = $is_custom ? $permalink_structure : '';

// サイトURLを取得（プレビュー表示用）
$site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
          . '://' . ($_SERVER['HTTP_HOST'] ?? 'example.com');

$page_title   = '基本設定';
$current_page = 'settings';
$sub_page     = 'basic';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="wp-content-title-area"><h2>基本設定</h2></div>

<div class="settings-container">
    <form id="settingsForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_settings">

        <!-- ========== サイト情報 ========== -->
        <div class="settings-card">
            <h3>サイト情報</h3>
            <div class="form-group" style="margin-bottom:15px;">
                <label>サイト名</label>
                <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>" required style="width:100%;">
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label>キャッチコピー / 説明文</label>
                <input type="text" name="site_description" value="<?php echo htmlspecialchars($site_description, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;">
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label>フッター表記</label>
                <input type="text" name="footer_text" value="<?php echo htmlspecialchars($footer_text, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;">
            </div>
        </div>

        <!-- ========== ロゴ & ファビコン ========== -->
        <div class="settings-card" style="margin-top:20px;">
            <h3>ロゴ ＆ ファビコン</h3>
            <div class="form-group" style="margin-bottom:15px;">
                <label>サイトロゴ</label>
                <?php if ($logo_image_path && file_exists(__DIR__ . '/../' . $logo_image_path)): ?>
                    <div class="image-preview-wrap"><img src="../<?php echo htmlspecialchars($logo_image_path, ENT_QUOTES, 'UTF-8'); ?>" style="max-height:50px;"></div>
                <?php endif; ?>
                <input type="file" name="logo_image" accept="image/*">
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label>ファビコン</label>
                <?php if ($favicon_image_path && file_exists(__DIR__ . '/../' . $favicon_image_path)): ?>
                    <div class="image-preview-wrap"><img src="../<?php echo htmlspecialchars($favicon_image_path, ENT_QUOTES, 'UTF-8'); ?>" style="max-height:32px;"></div>
                <?php endif; ?>
                <input type="file" name="favicon_image" accept="image/*">
            </div>
        </div>

        <!-- ========== パーマリンク設定 ========== -->
        <div class="settings-card" style="margin-top:20px;">
            <h3>パーマリンク設定</h3>
            <p style="margin-bottom:18px; color:#555; font-size:.9em;">
                URLをカスタマイズすることで、リンクの美しさや使いやすさ、そして SEO を改善できます。<br>
                <code>%postname%</code> タグを含めると投稿が検索エンジンで上位に表示されやすくなります。
            </p>

            <table class="permalink-table" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th style="text-align:left; padding:6px 0; font-weight:600;">構造</th>
                        <th style="text-align:left; padding:6px 0; font-weight:600; color:#888;">URLプレビュー</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $presets = [
                    [
                        'label'   => '基本',
                        'value'   => 'plain',
                        'preview' => '?p=123',
                    ],
                    [
                        'label'   => '日付と投稿名',
                        'value'   => '/%year%/%monthnum%/%day%/%postname%/',
                        'preview' => '/2026/06/12/sample-post/',
                    ],
                    [
                        'label'   => '月と投稿名',
                        'value'   => '/%year%/%monthnum%/%postname%/',
                        'preview' => '/2026/06/sample-post/',
                    ],
                    [
                        'label'   => '数字ベース',
                        'value'   => '/archives/%post_id%',
                        'preview' => '/archives/123',
                    ],
                    [
                        'label'   => '投稿名',
                        'value'   => '/%postname%/',
                        'preview' => '/sample-post/',
                    ],
                ];
                foreach ($presets as $preset):
                    $checked = (!$is_custom && $permalink_structure === $preset['value']) ? 'checked' : '';
                ?>
                <tr class="permalink-row" style="border-top:1px solid #eee;">
                    <td style="padding:12px 8px 12px 0; vertical-align:middle;">
                        <input type="radio" name="permalink_structure" value="<?php echo htmlspecialchars($preset['value'], ENT_QUOTES); ?>"
                               id="perm_<?php echo $preset['value']; ?>" <?php echo $checked; ?> style="margin:0;">
                    </td>
                    <td style="padding:12px 8px; vertical-align:middle;">
                        <label for="perm_<?php echo $preset['value']; ?>" style="cursor:pointer; font-weight:500;"><?php echo $preset['label']; ?></label>
                    </td>
                    <td style="padding:12px 8px; vertical-align:middle; color:#777; font-size:.88em;">
                        <span style="background:#f5f5f5; padding:3px 8px; border-radius:4px; font-family:monospace;">
                            <?php echo htmlspecialchars($site_url . $preset['preview'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- カスタム構造 -->
                <tr class="permalink-row" style="border-top:1px solid #eee;">
                    <td style="padding:12px 8px 12px 0; vertical-align:top; padding-top:15px;">
                        <input type="radio" name="permalink_structure" value="custom"
                               id="perm_custom" <?php echo $is_custom ? 'checked' : ''; ?> style="margin:0;">
                    </td>
                    <td style="padding:12px 8px; vertical-align:top;">
                        <label for="perm_custom" style="cursor:pointer; font-weight:500;">カスタム構造</label>
                        <div style="margin-top:8px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="color:#888; font-size:.88em; font-family:monospace;"><?php echo htmlspecialchars($site_url, ENT_QUOTES, 'UTF-8'); ?></span>
                            <input type="text" id="custom_permalink_input" name="custom_permalink_structure"
                                   value="<?php echo htmlspecialchars($custom_value, ENT_QUOTES, 'UTF-8'); ?>"
                                   placeholder="/%category%/%postname%/"
                                   style="width:220px; font-family:monospace; padding:5px 8px;">
                        </div>
                        <!-- 利用可能なタグ -->
                        <div style="margin-top:10px;">
                            <span style="font-size:.82em; color:#666; margin-right:6px;">利用可能なタグ:</span>
                            <?php
                            $tags = ['%year%','%monthnum%','%day%','%hour%','%minute%','%second%','%post_id%','%postname%','%category%','%author%'];
                            foreach ($tags as $tag): ?>
                            <button type="button" class="tag-btn"
                                    data-tag="<?php echo htmlspecialchars($tag); ?>"
                                    style="display:inline-block; margin:3px 2px; padding:3px 9px; font-size:.82em;
                                           border:1px solid #c3c4c7; border-radius:3px; background:#fff;
                                           cursor:pointer; font-family:monospace;">
                                <?php echo htmlspecialchars($tag); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td></td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- ========== 機能設定 ========== -->
        <div class="settings-card" style="margin-top:20px;">
            <h3>機能設定</h3>
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="enable_custom_post" value="1" <?php echo $enable_custom_post ? 'checked' : ''; ?>>
                    カスタム投稿タイプを有効にする
                </label>
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="button button-primary">変更を保存</button>
        </div>
    </form>
</div>

<script>
(function () {
    const customRadio = document.getElementById('perm_custom');
    const customInput = document.getElementById('custom_permalink_input');

    // タグボタンでカスタム入力欄にタグを挿入
    document.querySelectorAll('.tag-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            customRadio.checked = true;
            const tag = this.dataset.tag;
            const start = customInput.selectionStart;
            const end   = customInput.selectionEnd;
            const val   = customInput.value;
            customInput.value = val.slice(0, start) + tag + val.slice(end);
            customInput.focus();
            customInput.setSelectionRange(start + tag.length, start + tag.length);
        });
    });

    // カスタム入力欄にフォーカスしたらカスタムラジオを自動選択
    customInput.addEventListener('focus', function () {
        customRadio.checked = true;
    });

    document.getElementById('settingsForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        // カスタム構造の場合、実際の値を permalink_structure に上書き
        if (document.getElementById('perm_custom').checked) {
            const customVal = customInput.value.trim();
            if (!customVal) {
                alert('カスタム構造を選択した場合はURL構造を入力してください。');
                customInput.focus();
                return;
            }
            formData.set('permalink_structure', customVal);
        }
        formData.delete('custom_permalink_structure');

        fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('設定を保存しました！');
                    window.location.reload();
                } else {
                    alert('保存に失敗しました: ' + (data.error || '不明なエラー'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('通信エラーが発生しました。');
            });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>