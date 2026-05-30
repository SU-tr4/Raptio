<?php
// admin/includes/header.php
if (!defined('DATA_DIR')) {
    exit; // 直接アクセス禁止
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . ' &lsaquo; ' : ''; ?>Raptio</title>
    <link rel="icon" href="./img/Raptio_icon.png" type="image/png">
    <link rel="stylesheet" href="./css/admin_style.css">
    <link rel="stylesheet" href="./css/admin_editor.css">
    <link rel="stylesheet" href="./css/admin_settings.css">
    <link rel="stylesheet" href="./css/admin_widgets.css">
</head>
<body>
<div class="wp-admin-wrapper">
    <div class="wp-admin-header-bar">
        <div class="brand-area">
            <img src="./img/logo1.png" alt="Raptio" class="admin-logo">
        </div>
        <div class="user-info">こんにちは、管理者 さん</div>
    </div>
    
    <div class="wp-admin-body-container">