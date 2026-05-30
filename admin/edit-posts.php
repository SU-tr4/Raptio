<?php
// admin/edit-posts.php
require_once __DIR__ . '/auth.php';

// 未認証時はダッシュボードへ強制リダイレクト（ログインを挟むため）
if (!check_raptio_auth()) {
    header('Location: index.php');
    exit;
}

$page_title = '投稿一覧';
$current_page = 'posts';
$sub_page = 'list';

$index_data = file_exists(INDEX_FILE) ? json_decode(file_get_contents(INDEX_FILE), true) : [];
$posts = is_array($index_data) ? $index_data : [];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="wp-content-title-area">
    <h2>投稿</h2>
    <a href="editor.php" class="button button-primary">新規追加</a>
</div>

<table class="wp-list-table">
    <thead>
        <tr>
            <th>タイトル</th>
            <th>スラッグ</th>
            <th>日付</th>
            <th>ステータス</th>
            <th style="width:180px; text-align:center;">操作</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($posts)): ?>
            <tr>
                <td colspan="5" style="text-align:center; padding:30px; color:#646970;">投稿がありません。最初の記事を作成しましょう！</td>
            </tr>
        <?php else: foreach ($posts as $post): ?>
            <tr>
                <td><strong style="font-size:14px; color:#1d2327;"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td><code><?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                <td><?php echo htmlspecialchars($post['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <span class="wp-badge badge-<?php echo $post['status']; ?>">
                        <?php echo $post['status'] === 'public' ? '公開済み' : '下書き'; ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <a href="editor.php?id=<?php echo $post['id']; ?>" class="button button-secondary" style="line-height:2; min-height:26px; padding:0 8px;">編集</a>
                    <button onclick="deletePost('<?php echo $post['id']; ?>')" class="button-link-delete" style="margin-left:10px;">ゴミ箱へ移動</button>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<script>
    function deletePost(id) {
        if (confirm('この記事を完全に削除（ゴミ箱を空に）しますか？')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { if (data.success) { location.reload(); } else { alert('エラー: ' + data.error); } });
        }
    }
</script>
<?php
require_once __DIR__ . '/includes/footer.php';
?>