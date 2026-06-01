<aside class="sidebar-content">
    <?php 
    
    if (class_exists('WidgetManager')) {
        WidgetManager::renderArea('sidebar', $site_config['widgets']['sidebar'] ?? [], $posts, $all_categories);
    }
    ?>
</aside>