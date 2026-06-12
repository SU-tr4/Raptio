<?php
// admin/includes/sidebar.php
if (!defined('DATA_DIR')) {
    exit;
}
$current_page = $current_page ?? 'index';
$sub_page     = $sub_page ?? '';

// URL算出ロジック
$_admin_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_admin_url    = $_admin_scheme . '://' . $_SERVER['HTTP_HOST']
               . str_replace('\\', '/', substr(rtrim(realpath(__DIR__ . '/..'), '/\\'),
                   strlen(rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/'))));

// カスタム投稿の有効状態と登録リストを取得
$config_data_sidebar = defined('CONFIG_FILE') && file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
$is_cpt_enabled = !empty($config_data_sidebar['enable_custom_post']);

$cpt_list = [];
if ($is_cpt_enabled && defined('CPT_CONFIG_FILE') && file_exists(CPT_CONFIG_FILE)) {
    $cpt_list = json_decode(file_get_contents(CPT_CONFIG_FILE), true) ?? [];
}
?>
<div class="wp-sidebar">
    <ul class="wp-sidebar-menu">
        <li><a href="<?php echo $_admin_url; ?>/index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">ダッシュボード</a></li>
        
        <li class="has-submenu <?php echo $current_page === 'posts' ? 'open' : ''; ?>">
            <a href="javascript:void(0);">投稿</a>
            <ul class="wp-sidebar-submenu">
                <li><a href="<?php echo $_admin_url; ?>/edit-posts.php" class="<?php echo $sub_page === 'list' ? 'active' : ''; ?>">投稿一覧</a></li>
                <li><a href="<?php echo $_admin_url; ?>/editor.php" class="<?php echo $sub_page === 'add' ? 'active' : ''; ?>">新規投稿</a></li>
                <li><a href="<?php echo $_admin_url; ?>/categories.php" class="<?php echo $sub_page === 'categories' ? 'active' : ''; ?>">カテゴリー</a></li>
            </ul>
        </li>

        <li class="has-submenu <?php echo $current_page === 'pages' ? 'open' : ''; ?>">
            <a href="javascript:void(0);">単独</a>
            <ul class="wp-sidebar-submenu">
                <li><a href="<?php echo $_admin_url; ?>/edit-pages.php" class="<?php echo $sub_page === 'list' ? 'active' : ''; ?>">単独ページ一覧</a></li>
                <li><a href="<?php echo $_admin_url; ?>/editor.php?mode=page" class="<?php echo $sub_page === 'add' ? 'active' : ''; ?>">単独ページ追加</a></li>
            </ul>
        </li>

        <?php if ($is_cpt_enabled && !empty($cpt_list)): ?>
            <?php foreach ($cpt_list as $cpt_slug => $cpt): ?>
            <li class="has-submenu <?php echo ($current_page === 'cpt_' . $cpt_slug) ? 'open' : ''; ?>">
                <a href="javascript:void(0);"><?php echo htmlspecialchars($cpt['label']); ?></a>
                <ul class="wp-sidebar-submenu">
                    <li><a href="<?php echo $_admin_url; ?>/edit-posts.php?type=<?php echo $cpt_slug; ?>" class="<?php echo ($current_page === 'cpt_' . $cpt_slug && $sub_page === 'list') ? 'active' : ''; ?>">一覧</a></li>
                    <li><a href="<?php echo $_admin_url; ?>/editor.php?type=<?php echo $cpt_slug; ?>" class="<?php echo ($current_page === 'cpt_' . $cpt_slug && $sub_page === 'add') ? 'active' : ''; ?>">新規追加</a></li>
                </ul>
            </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <li><a href="<?php echo $_admin_url; ?>/media.php" class="<?php echo $current_page === 'media' ? 'active' : ''; ?>">メディア</a></li>

        <li class="has-submenu <?php echo in_array($current_page, ['themes', 'plugins']) ? 'open' : ''; ?>">
            <a href="javascript:void(0);">サイトデザイン</a>
            <ul class="wp-sidebar-submenu">
                <li><a href="<?php echo $_admin_url; ?>/themes.php" class="<?php echo $current_page === 'themes' ? 'active' : ''; ?>">テーマ管理</a></li>
                <li><a href="<?php echo $_admin_url; ?>/plugins.php" class="<?php echo $current_page === 'plugins' ? 'active' : ''; ?>">プラグイン管理</a></li>
            </ul>
        </li>

        <li class="has-submenu <?php echo in_array($current_page, ['settings', 'menu', 'sidebar', 'cpt_settings']) ? 'open' : ''; ?>">
            <a href="javascript:void(0);">設定</a>
            <ul class="wp-sidebar-submenu">
                <li><a href="<?php echo $_admin_url; ?>/site-settings.php" class="<?php echo $current_page === 'settings' ? 'active' : ''; ?>">基本設定</a></li>
                <?php if ($is_cpt_enabled): ?>
                <li><a href="<?php echo $_admin_url; ?>/cpt-settings.php" class="<?php echo $current_page === 'cpt_settings' ? 'active' : ''; ?>">CPT設定</a></li>
                <?php endif; ?>
                <li><a href="<?php echo $_admin_url; ?>/site-menu.php" class="<?php echo $current_page === 'menu' ? 'active' : ''; ?>">メニュー管理</a></li>
                <li><a href="<?php echo $_admin_url; ?>/site-sidebar.php" class="<?php echo $current_page === 'sidebar' ? 'active' : ''; ?>">サイドバー管理</a></li>
            </ul>
        </li>

        <li class="menu-separator"></li>
        <?php
        ob_start();
        RaptioHook::do('admin_sidebar_menu');
        echo ob_get_clean();
        ?>
        <li><a href="<?php echo $_admin_url; ?>/../" target="_blank" style="color: #72aee6;">サイト確認 ↗</a></li>
        <li><a href="<?php echo $_admin_url; ?>/index.php?action=logout" style="color: #f39c12;">ログアウト</a></li>
    </ul>
</div>
<div class="wp-main-content">