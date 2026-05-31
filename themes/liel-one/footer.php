<?php if (!empty($site_config['widgets']['sidebar'])) : ?>
        <aside class="footer-widgets" aria-label="ウィジェットエリア">
            <?php foreach ($site_config['widgets']['sidebar'] as $widget) : ?>
                <div class="widget-item">
                    <h3><?php echo htmlspecialchars($widget['title'] ?? 'ウィジェット'); ?></h3>
                    <div class="widget-content">
                        <?php echo $widget['content'] ?? ''; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </aside>
    <?php endif; ?>

    <footer>
        <p><?php echo htmlspecialchars($site_config['footer_text'] ?? 'Parallel Project.'); ?></p>
    </footer>
</div>
</body>
</html>