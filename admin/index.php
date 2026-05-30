<?php
// admin/index.php
require_once __DIR__ . '/auth.php';

// 未認証時のログインフォーム表示
if (!check_raptio_auth()) {
    require_once __DIR__ . '/login.php';
    exit;
}

// 認証済みの場合のダッシュボード処理
$page_title = 'ダッシュボード';
$current_page = 'index';

$index_data = file_exists(INDEX_FILE) ? json_decode(file_get_contents(INDEX_FILE), true) : [];
$posts = is_array($index_data) ? $index_data : [];
$post_count = count($posts);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="wp-content-title-area">
    <h2>ダッシュボード</h2>
</div>

<div class="welcome-panel">
    <h3>Raptio へようこそ！</h3>
    <p style="color: #646970; font-size: 14px;">新しい記事の執筆やサイトのカスタマイズはこちらから始めてください。</p>
</div>

<div class="dashboard-widgets">
    <div class="settings-card">
        <h3>概要</h3>
        <ul>
            <li>現在の投稿数: <strong><?php echo $post_count; ?> 件</strong></li>
            <li>システム状態: <span style="color: #2e7d32; font-weight: bold;">正常稼働中</span></li>
        </ul>
        <div style="margin-top: 15px;">
            <a href="edit-posts.php" class="button button-secondary">投稿を管理</a>
        </div>
    </div>

    <div class="settings-card">
        <h3>クイックドラフト</h3>
        <form action="editor.php" method="GET">
            <div style="margin-bottom: 12px;">
                <input type="text" placeholder="タイトル">
            </div>
            <div style="margin-bottom: 12px;">
                <textarea placeholder="今考えていることは？" style="height: 80px;"></textarea>
            </div>
            <button type="submit" class="button button-primary">下書きとして開始</button>
        </form>
    </div>
</div>
<?php
require_once __DIR__ . '/includes/footer.php';
?>