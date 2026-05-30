<?php
// admin/includes/sidebar.php
if (!defined('DATA_DIR')) {
    exit;
}
$current_page = $current_page ?? 'index';
$sub_page = $sub_page ?? '';
?>
<div class="wp-sidebar">
    <ul class="wp-sidebar-menu">
        <li><a href="index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">ダッシュボード</a></li>
        <li>
            <a href="edit-posts.php" class="<?php echo $current_page === 'posts' ? 'active' : ''; ?>">投稿</a>
            <ul class="wp-sidebar-submenu">
                <li><a href="edit-posts.php" class="<?php echo ($current_page === 'posts' && $sub_page === 'list') ? 'active' : ''; ?>">投稿一覧</a></li>
                <li><a href="editor.php" class="<?php echo ($current_page === 'posts' && $sub_page === 'add') ? 'active' : ''; ?>">投稿を追加</a></li>
                <li><a href="categories.php" class="<?php echo ($current_page === 'posts' && $sub_page === 'categories') ? 'active' : ''; ?>">カテゴリー</a></li>
            </ul>
        </li>
        <li><a href="media.php" class="<?php echo $current_page === 'media' ? 'active' : ''; ?>">メディア</a></li>
        
        <li>
            <a href="#" class="<?php echo $current_page === 'settings' ? 'active' : ''; ?>">サイトデザイン</a>
            <ul class="wp-sidebar-submenu">
                <li><a href="site-settings.php">基本設定</a></li>
                <li><a href="themes.php">テーマ管理</a></li>
                <li><a href="site-menu.php">メニュー管理</a></li>
                <li><a href="site-sidebar.php">サイドバー管理</a></li>
            </ul>
        </li>
        
        <li class="menu-separator"></li>
        <li><a href="../" target="_blank" style="color: #72aee6;">サイト確認 ↗</a></li>
        <li><a href="index.php?action=logout" style="color: #f39c12;">ログアウト</a></li>
    </ul>
</div>
<div class="wp-main-content">