<?php global $site_config; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_config['site_name']); ?></title>

    <?php if (!empty($site_config['favicon_image_path'])) : ?>
        <link rel="icon" href="<?php echo htmlspecialchars($site_config['favicon_image_path']); ?>">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="themes/liel-clarion/style.css">
</head>
<body>

<header class="site-header">
    <div class="site-header__inner">
        <div class="site-header__topbar">
            <span><?php echo date('Y年n月j日'); ?></span>
            <?php if (!empty($site_config['site_description'])) : ?>
                <span><?php echo htmlspecialchars($site_config['site_description']); ?></span>
            <?php endif; ?>
        </div>

        <div class="site-header__brand">
            <?php if (!empty($site_config['logo_image_path'])) : ?>
                <div class="site-logo">
                    <a href="index.php">
                        <img src="<?php echo htmlspecialchars($site_config['logo_image_path']); ?>" alt="<?php echo htmlspecialchars($site_config['site_name']); ?>">
                    </a>
                </div>
            <?php else : ?>
                <h1><a href="index.php"><?php echo htmlspecialchars($site_config['site_name']); ?></a></h1>
                <p><?php echo htmlspecialchars($site_config['site_description'] ?? ''); ?></p>
            <?php endif; ?>
        </div>

        <?php
        $menu_id = $site_config['menu_locations']['header'] ?? null;
        if ($menu_id && isset($site_config['menus'][$menu_id])) : ?>
            <nav class="site-nav" aria-label="メインナビゲーション">
                <ul>
                    <?php foreach ($site_config['menus'][$menu_id]['items'] as $item) : ?>
                        <li><a href="<?php echo htmlspecialchars($item['url']); ?>"><?php echo htmlspecialchars($item['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</header>

<div class="site-rule"></div>

<div class="site-wrap">
    <div class="site-body">