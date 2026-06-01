<?php
// themes/lux/functions.php

/**
 * サイドバーウィジェットのレンダリング関数
 */
function render_sidebar_widgets($widgets) {
    if (empty($widgets) || !is_array($widgets)) {
        return;
    }

    foreach ($widgets as $widget) {
        $type = $widget['type'] ?? 'text';
        $title = $widget['title'] ?? '';
        
        echo '<div class="sidebar-widget">';
        if (!empty($title)) {
            echo '<div class="sidebar-widget-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        
        echo '<div class="sidebar-widget-body">';
        
        // ウィジェットタイプに応じた出力処理
        if ($type === 'text') {
            echo nl2br(htmlspecialchars($widget['content'] ?? '', ENT_QUOTES, 'UTF-8'));
        } elseif ($type === 'html') {
            echo $widget['html_code'] ?? '';
        } else {
            // その他のタイプやデフォルトの挙動
            echo nl2br(htmlspecialchars($widget['content'] ?? '', ENT_QUOTES, 'UTF-8'));
        }
        
        echo '</div>'; // sidebar-widget-body
        echo '</div>'; // sidebar-widget
    }
}