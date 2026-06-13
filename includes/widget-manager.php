<?php
/**
 * includes/widget-manager.php
 * 修正版: ルートからの絶対パスでURLを生成するよう変更
 */

class WidgetManager {
    // サイトのルートベースパスを計算
    private static function getBasePath() {
        return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    }

    public static function renderArea($areaId, $widgets, $posts, $all_categories) {
        if (empty($widgets) || !is_array($widgets)) return;
        foreach ($widgets as $widget) {
            self::renderWidget($widget, $posts, $all_categories);
        }
    }

    private static function renderWidget($widget, $posts, $all_categories) {
        $type = $widget['type'] ?? 'text';
        $title = $widget['title'] ?? '';

        echo '<div class="sidebar-widget">';
        if (!empty($title)) {
            echo '<div class="sidebar-widget-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        echo '<div class="sidebar-widget-body">';

        switch ($type) {
            case 'recent':
            case 'popular':
                self::renderRecent($posts, $widget['count'] ?? 5, $widget['layout'] ?? 'title-only');
                break;
            case 'categories':
                self::renderCategories($all_categories, $posts);
                break;
            case 'search':
                echo '<form action="' . self::getBasePath() . '/index.php" method="GET" class="sidebar-search-form">
                        <input type="text" name="s" value="' . htmlspecialchars($_GET['s'] ?? '', ENT_QUOTES, 'UTF-8') . '" placeholder="検索キーワード..." aria-label="検索">
                      </form>';
                break;
            case 'html':
                echo $widget['html_code'] ?? '';
                break;
            case 'text':
            default:
                echo nl2br(htmlspecialchars($widget['content'] ?? '', ENT_QUOTES, 'UTF-8'));
                break;
        }
        echo '</div></div>';
    }

    private static function renderRecent($posts, $count, $layout) {
        $base = self::getBasePath();
        echo '<ul class="recent-list recent-list-' . htmlspecialchars($layout, ENT_QUOTES, 'UTF-8') . '">';
        $i = 0;
        foreach ($posts as $p) {
            if (($p['status'] ?? '') === 'public') {
                $title = htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8');
                
                // 日付取得
                $date_str = $p['date'] ?? date('Y-m-d');
                $d = explode('-', explode(' ', $date_str)[0]);
                $year  = $d[0] ?? date('Y');
                $month = $d[1] ?? date('m');
                $day   = $d[2] ?? date('d');
                $slug  = $p['slug'] ?? '';
                
                // ルートからの絶対パスを生成
                $url = $base . '/' . $year . '/' . $month . '/' . $day . '/' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '/';

                $thumb = !empty($p['thumbnail']) ? $p['thumbnail'] : 'assets/no-image.png';
                echo '<li><a href="'.$url.'" class="item-link">';
                if (strpos($layout, 'thumb') !== false) {
                    echo '<img src="'.$thumb.'" alt="">';
                }
                if ($layout !== 'thumb-only') echo '<span class="title">'.$title.'</span>';
                echo '</a></li>';
                if (++$i >= $count) break;
            }
        }
        echo '</ul>';
    }

    private static function renderCategories($all_categories, $posts) {
        $base = self::getBasePath();
        echo '<ul class="sidebar-cat-list">';
        foreach ($all_categories as $cat) {
            $cnt = 0;
            foreach ($posts as $p) {
                if (($p['status'] ?? '') === 'public' && ($p['category_id'] ?? '') === $cat['id']) $cnt++;
            }
            // ルートからの絶対パスを生成
            $url = $base . '/category/' . htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') . '/';
            echo '<li><a href="'.$url.'">' . htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') . ' ('.$cnt.')</a></li>';
        }
        echo '</ul>';
    }
}