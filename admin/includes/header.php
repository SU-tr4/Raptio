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
            <img src="<?php echo $_admin_url; ?>/img/logo1.png" alt="Raptio" class="admin-logo">
        </div>
        <div class="user-info">こんにちは、管理者 さん</div>
    </div>
    
    <div class="wp-admin-body-container">