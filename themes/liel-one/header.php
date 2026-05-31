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
    <link rel="stylesheet" href="themes/liel-one/style.css">
</head>
<body>
<div class="container">
    <header>
        <?php if (!empty($site_config['logo_image_path'])) : ?>
            <div class="site-logo">
                <a href="index.php">
                    <img src="<?php echo htmlspecialchars($site_config['logo_image_path']); ?>" alt="<?php echo htmlspecialchars($site_config['site_name']); ?>">
                </a>
            </div>
        <?php else : ?>
            <h1><a href="index.php"><?php echo htmlspecialchars($site_config['site_name']); ?></a></h1>
        <?php endif; ?>

        <?php if (!empty($site_config['site_description'])) : ?>
            <p><?php echo htmlspecialchars($site_config['site_description']); ?></p>
        <?php endif; ?>

        <?php
        $menu_id = $site_config['menu_locations']['header'] ?? null;
        if ($menu_id && isset($site_config['menus'][$menu_id])) : ?>
            <nav class="main-menu" aria-label="メインナビゲーション">
                <ul>
                    <?php foreach ($site_config['menus'][$menu_id]['items'] as $item) : ?>
                        <li><a href="<?php echo htmlspecialchars($item['url']); ?>"><?php echo htmlspecialchars($item['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </header>