<?php
// admin/cpt-settings.php
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }

$page_title = 'カスタム投稿タイプ設定';
$current_page = 'cpt';
$sub_page = 'settings';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 既存のカスタム投稿設定があれば読み込む (cpt_config.json)
$cpt_file = defined('DATA_DIR') ? DATA_DIR . '/cpt_config.json' : __DIR__ . '/../data/cpt_config.json';
$cpt_data = file_exists($cpt_file) ? json_decode(file_get_contents($cpt_file), true) : [];
?>

<div class="wp-content-title-area">
    <h2>カスタム投稿タイプ設定</h2>
</div>

<div class="settings-container">
    <form id="cptSettingsForm">
        <input type="hidden" name="action" value="save_cpt_settings">

        <div class="settings-card">
            <h3>新規追加</h3>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>表示名（ラベル）</label>
                <input type="text" name="cpt_label" placeholder="例: お知らせ" required style="width:100%;">
                <p class="description">管理画面のサイドバーや一覧に表示される名前です。</p>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>スラッグ（URLの一部になります）</label>
                <input type="text" name="cpt_slug" placeholder="例: news" required style="width:100%;" pattern="^[a-zA-Z0-9_-]+$">
                <p class="description">半角英数字とハイフン、アンダースコアのみ使用可能です。（例: news, works）</p>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="button button-primary">カスタム投稿を追加・保存</button>
            </div>
        </div>
    </form>

    <div class="settings-card" style="margin-top: 20px;">
        <h3>登録済みのカスタム投稿タイプ</h3>
        <?php if (!empty($cpt_data)): ?>
            <table class="wp-list-table">
                <thead>
                    <tr>
                        <th>表示名</th>
                        <th>スラッグ</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cpt_data as $slug => $cpt): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cpt['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><code><?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?></code></td>
                        <td>
                            <button type="button" class="button button-link-delete" onclick="deleteCPT('<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>')">削除</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-size: 13px; color: #646970;">現在登録されているカスタム投稿タイプはありません。</p>
        <?php endif; ?>
    </div>
</div>

<script>
    document.getElementById('cptSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('カスタム投稿タイプを保存しました。');
                window.location.reload();
            } else {
                alert('保存に失敗しました: ' + (data.error || '不明なエラー'));
            }
        });
    });

    function deleteCPT(slug) {
        if (confirm('本当に「' + slug + '」を削除しますか？\n（※作成済みの記事データ自体は削除されませんが、管理画面からアクセスできなくなります）')) {
            const formData = new FormData();
            formData.append('action', 'delete_cpt_settings');
            formData.append('cpt_slug', slug);
            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('削除しました。');
                    window.location.reload();
                } else {
                    alert('削除に失敗しました: ' + (data.error || '不明なエラー'));
                }
            });
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>