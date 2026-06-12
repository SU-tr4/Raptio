<?php
// admin/includes/header.php
if (!defined('DATA_DIR')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . ' &lsaquo; ' : ''; ?>Raptio</title>
    <?php
    $_admin_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_admin_url    = $_admin_scheme . '://' . $_SERVER['HTTP_HOST']
                   . str_replace('\\', '/', substr(rtrim(realpath(__DIR__ . '/..'), '/\\'),
                       strlen(rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/'))));
    ?>
    <link rel="icon"       href="<?php echo $_admin_url; ?>/img/Raptio_icon.png" type="image/png">
    <link rel="stylesheet" href="<?php echo $_admin_url; ?>/css/admin_style.css">
    <link rel="stylesheet" href="<?php echo $_admin_url; ?>/css/admin_editor.css">
    <link rel="stylesheet" href="<?php echo $_admin_url; ?>/css/admin_settings.css">
    <link rel="stylesheet" href="<?php echo $_admin_url; ?>/css/admin_widgets.css">
    <?php RaptioHook::do('admin_head'); ?>
</head>
<body>
<div class="wp-admin-wrapper">
    <div class="wp-admin-header-bar">
        <div class="brand-area">
            <a href="https://raptio.site" target="_blank">
                <img src="<?php echo $_admin_url; ?>/img/logo1.png" alt="Raptio" class="admin-logo">
            </a>
            
            <div class="header-actions">
                <div class="header-dropdown">
                    <button class="dropdown-toggle">新規作成</button>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo $_admin_url; ?>/editor.php">新規投稿</a></li>
                        <li><a href="<?php echo $_admin_url; ?>/editor.php?mode=page">固定ページ追加</a></li>
                    </ul>
                </div>
                <a href="<?php echo $_admin_url; ?>/../" target="_blank" class="site-preview-btn">サイト確認</a>
            </div>
        </div>
        <div class="user-info">こんにちは、管理者 さん</div>
    </div>
    
    <div class="wp-admin-body-container">