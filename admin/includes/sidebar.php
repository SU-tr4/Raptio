<?php
// admin/includes/sidebar.php
if (!defined('DATA_DIR')) {
    exit;
}
$current_page = $current_page ?? 'index';
$sub_page     = $sub_page ?? '';

// header.php と同じ方法で admin/ の絶対URLを算出
$_admin_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_admin_url    = $_admin_scheme . '://' . $_SERVER['HTTP_HOST']
               . str_replace('\\', '/', substr(rtrim(realpath(__DIR__ . '/..'), '/\\'),
                   strlen(rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/'))));
// 末尾スラッシュなし: http://localhost/raptio/admin
?>
<div class="wp-sidebar">
    <ul class="wp-sidebar-menu">
        <li><a href="<?php echo $_admin_url; ?>/index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">ダッシュボード</a></li>
        <li>
            <a href="<?php echo $_admin_url; ?>/edit-posts.php" class="<?php echo $current_page === 'posts' ? 'active' : ''; ?>">投稿</a>
            <ul class="wp-sidebar-submenu">
                <li><a href="<?php echo $_admin_url; ?>/edit-posts.php" class="<?php echo ($current_page === 'posts' && $sub_page === 'list') ? 'active' : ''; ?>">投稿一覧</a></li>
                <li><a href="<?php echo $_admin_url; ?>/editor.php" class="<?php echo ($current_page === 'posts' && $sub_page === 'add') ? 'active' : ''; ?>">投稿を追加</a></li>
                <li><a href="<?php echo $_admin_url; ?>/categories.php" class="<?php echo ($current_page === 'posts' && $sub_page === 'categories') ? 'active' : ''; ?>">カテゴリー</a></li>
            </ul>
        </li>
        <li><a href="<?php echo $_admin_url; ?>/media.php" class="<?php echo $current_page === 'media' ? 'active' : ''; ?>">メディア</a></li>

        <li>
            <a href="<?php echo $_admin_url; ?>/site-settings.php" class="<?php echo in_array($current_page, ['settings', 'themes', 'plugins', 'menu', 'sidebar']) ? 'active' : ''; ?>">サイトデザイン</a>
            <ul class="wp-sidebar-submenu">
                <li><a href="<?php echo $_admin_url; ?>/site-settings.php" class="<?php echo $current_page === 'settings' ? 'active' : ''; ?>">基本設定</a></li>
                <li><a href="<?php echo $_admin_url; ?>/themes.php" class="<?php echo $current_page === 'themes' ? 'active' : ''; ?>">テーマ管理</a></li>
                <li><a href="<?php echo $_admin_url; ?>/plugins.php" class="<?php echo $current_page === 'plugins' ? 'active' : ''; ?>">プラグイン管理</a></li>
                <li><a href="<?php echo $_admin_url; ?>/site-menu.php" class="<?php echo $current_page === 'menu' ? 'active' : ''; ?>">メニュー管理</a></li>
                <li><a href="<?php echo $_admin_url; ?>/site-sidebar.php" class="<?php echo $current_page === 'sidebar' ? 'active' : ''; ?>">サイドバー管理</a></li>
            </ul>
        </li>

        <li class="menu-separator"></li>
        <?php
        // プラグインフックの出力をバッファリングしてURLを正規化
        ob_start();
        RaptioHook::do('admin_sidebar_menu');
        $plugin_menu_html = ob_get_clean();

        // href の相対パス（/ や http で始まらないもの）を正規化
        // 例: "wp-importer-page.php" → "/raptio/admin/plugin-page.php?plugin=wp-importer"
        $plugin_menu_html = preg_replace_callback(
            '/href=["\'](?!https?:\/\/|\/|#)([^"\'?#]*)([^"\']*)["\']/',
            function ($matches) use ($_admin_url) {
                $file  = $matches[1]; // 例: wp-importer-page.php
                $query = $matches[2]; // 例: ?foo=bar（あれば）

                // ファイル名だけ取り出す（ディレクトリ部分は捨てる）
                $basename = basename($file);

                // すでに plugin-page.php?plugin=XXX 形式なら絶対URLに変換するだけでOK
                if ($basename === 'plugin-page.php') {
                    return 'href="' . $_admin_url . '/plugin-page.php' . $query . '"';
                }

                // "-page.php" で終わるファイルはプラグインページとしてルーターに通す
                if (preg_match('/^(.+)-page\.php$/', $basename, $m)) {
                    $plugin_name = $m[1]; // wp-importer
                    return 'href="' . $_admin_url . '/plugin-page.php?plugin=' . urlencode($plugin_name) . '"';
                }

                // それ以外は admin/ 直下のファイルとして絶対パスに変換
                return 'href="' . $_admin_url . '/' . $basename . $query . '"';
            },
            $plugin_menu_html
        );

        echo $plugin_menu_html;
        ?>
        <li><a href="<?php echo $_admin_url; ?>/../" target="_blank" style="color: #72aee6;">サイト確認 ↗</a></li>
        <li><a href="<?php echo $_admin_url; ?>/index.php?action=logout" style="color: #f39c12;">ログアウト</a></li>
    </ul>
</div>
<div class="wp-main-content">