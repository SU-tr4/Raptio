<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="themes/lux/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <?php if (!empty($site_config['favicon_image_path']) && file_exists(SITE_ROOT . '/' . $site_config['favicon_image_path'])): ?>
        <link rel="icon" href="<?php echo htmlspecialchars($site_config['favicon_image_path'], ENT_QUOTES, 'UTF-8'); ?>" type="image/x-icon">
    <?php endif; ?>
</head>
<body>

    <header id="site-header">
        <div class="header-container">
            <a href="index.php" class="site-logo">
                <?php if (!empty($site_config['logo_image_path']) && file_exists(SITE_ROOT . '/' . $site_config['logo_image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($site_config['logo_image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($site_config['site_name'], ENT_QUOTES, 'UTF-8'); ?>" class="logo-image">
                <?php else: ?>
                    <span class="logo-text"><?php echo htmlspecialchars($site_config['site_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </a>
            <nav class="site-nav">
                <?php
                $header_gid = $site_config['menu_locations']['header'] ?? '';
                if ($header_gid && isset($site_config['menus'][$header_gid]['items'])) {
                    $menu_items = $site_config['menus'][$header_gid]['items'];
                    foreach ($menu_items as $item) {
                        $name = $item['label'] ?? '';
                        $url = $item['url'] ?? '';
                        if ($name !== '' && $url !== '') {
                            echo '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>';
                        }
                    }
                }
                ?>
            </nav>
        </div>
    </header>