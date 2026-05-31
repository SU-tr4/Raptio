<aside class="sidebar-content">
    
    <?php if (!empty($site_config['widgets']['sidebar'])): ?>
        <?php foreach ($site_config['widgets']['sidebar'] as $widget): ?>
            <div class="sidebar-widget">
                <div class="sidebar-widget-title"><?php echo htmlspecialchars($widget['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="sidebar-widget-body"><?php echo nl2br(htmlspecialchars($widget['content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="sidebar-widget">
        <div class="sidebar-widget-title">
            <?php echo htmlspecialchars($site_config['sidebar_title'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="sidebar-widget-body"><?php echo nl2br(htmlspecialchars($site_config['sidebar_content'], ENT_QUOTES, 'UTF-8')); ?></div>
    </div>

    <?php if (!empty($all_categories)): ?>
    <div class="sidebar-cat-widget">
        <div class="sidebar-widget-title" style="margin-top: 28px;">カテゴリー</div>
        <ul class="sidebar-cat-list">
            <?php foreach ($all_categories as $cat): ?>
                <?php
                $cnt = 0;
                foreach ($posts as $p) {
                    if (($p['status'] ?? '') === 'public' && ($p['category_id'] ?? '') === $cat['id']) $cnt++;
                }
                ?>
                <li>
                    <a href="index.php?cat=<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo ($filter_cat ?? '') === $cat['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                        <span class="cat-count"><?php echo $cnt; ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</aside>