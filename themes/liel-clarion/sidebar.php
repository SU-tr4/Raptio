<?php global $site_config; ?>
        <aside aria-label="サイドバー">
            <?php if (!empty($site_config['widgets']['sidebar'])) : ?>
                <?php foreach ($site_config['widgets']['sidebar'] as $widget) : ?>
                    <div class="widget">
                        <h3><?php echo htmlspecialchars($widget['title'] ?? 'ウィジェット'); ?></h3>
                        <div><?php echo $widget['content'] ?? ''; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </aside>