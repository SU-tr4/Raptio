<footer id="site-footer">
    <div class="container">
        <?php
        $widgets = $site_config['widgets'] ?? [];
        $f1 = $widgets['footer1'] ?? [];
        $f2 = $widgets['footer2'] ?? [];
        ?>

        <?php if (!empty($f1) || !empty($f2)): ?>
            <div class="footer-widgets">

                <?php if (!empty($f1)): ?>
                    <div class="footer-group-1">
                        <?php foreach ($f1 as $w): ?>
                            <div class="footer-col">
                                <?php if (!empty($w['title'])): ?>
                                    <h3 class="footer-widget-title"><?php echo htmlspecialchars($w['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php endif; ?>
                                <div class="footer-widget-body">
                                    <?php if (!empty($w['content'])): ?>
                                        <?php echo nl2br(htmlspecialchars($w['content'], ENT_QUOTES, 'UTF-8')); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($f2)): ?>
                    <div class="footer-group-2">
                        <?php foreach ($f2 as $w): ?>
                            <div class="footer-col">
                                <?php if (!empty($w['title'])): ?>
                                    <h3 class="footer-widget-title"><?php echo htmlspecialchars($w['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php endif; ?>
                                <div class="footer-widget-body">
                                    <?php if (!empty($w['content'])): ?>
                                        <?php echo nl2br(htmlspecialchars($w['content'], ENT_QUOTES, 'UTF-8')); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <div class="footer-flex">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_config['footer_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            
            <nav class="footer-nav">
                <?php
                $footer_gid = $site_config['menu_locations']['footer'] ?? '';
                if ($footer_gid && isset($site_config['menus'][$footer_gid]['items'])) {
                    foreach ($site_config['menus'][$footer_gid]['items'] as $item) {
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
    </div>
</footer>