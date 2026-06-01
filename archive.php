<?php
/**
 * archive.php - カテゴリー・検索対応アーカイブページ
 */

define('INDEX_FILE', __DIR__ . '/data/posts_index.json');
define('CONFIG_FILE', __DIR__ . '/data/site_config.json');
define('CATEGORIES_FILE', __DIR__ . '/data/categories.json');
define('INCLUDES_DIR', __DIR__ . '/includes');
define('SITE_ROOT', __DIR__);

require_once INCLUDES_DIR . '/widget-manager.php';

$site_config = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
$posts = file_exists(INDEX_FILE) ? json_decode(file_get_contents(INDEX_FILE), true) : [];
$all_categories = file_exists(CATEGORIES_FILE) ? json_decode(file_get_contents(CATEGORIES_FILE), true) : [];
$all_categories = is_array($all_categories) ? $all_categories : [];

$theme_dir = 'themes/' . ($site_config['active_theme'] ?? 'lux');
$cat_map = [];
foreach ($all_categories as $cat) { $cat_map[$cat['id']] = $cat['name']; }

// GETパラメータの取得
$filter_cat = $_GET['cat'] ?? '';
$search_query = trim($_GET['s'] ?? '');

// 投稿のフィルタリング処理
$public_posts = [];
foreach ($posts as $post) {
    if (($post['status'] ?? '') !== 'public') continue;
    
    // カテゴリーが指定されていれば絞り込む
    if ($filter_cat !== '' && ($post['category_id'] ?? '') !== $filter_cat) continue;
    
    // 検索キーワードが指定されていればタイトルで絞り込む
    if ($search_query !== '' && stripos($post['title'], $search_query) === false) continue;
    
    $public_posts[] = $post;
}

include __DIR__ . '/' . $theme_dir . '/header.php';
?>

<main class="container" style="margin-top: 40px;">
    <div class="layout-wrapper">
        <div class="main-content">
            <h1 class="page-title" style="margin-bottom: 30px;">
                <?php 
                if ($search_query !== '') echo '検索結果: ' . htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8');
                elseif ($filter_cat) echo 'Category: ' . htmlspecialchars($cat_map[$filter_cat] ?? '不明', ENT_QUOTES, 'UTF-8');
                else echo 'アーカイブ';
                ?>
            </h1>

            <div class="posts-list-stack">
                <?php if (!empty($public_posts)): ?>
                    <?php foreach ($public_posts as $post): ?>
                        <?php
                        $has_thumb = !empty($post['thumbnail']) && file_exists(SITE_ROOT . '/' . $post['thumbnail']);
                        $thumb_url = $has_thumb ? htmlspecialchars($post['thumbnail'], ENT_QUOTES, 'UTF-8') : '';
                        $cat_name = $cat_map[$post['category_id']] ?? '';
                        ?>
                        <article class="post-card <?php echo $has_thumb ? 'post-card--has-thumb' : ''; ?>">
                            <?php if ($has_thumb): ?>
                            <a href="index.php?slug=<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="post-card-thumb-link">
                                <div class="post-card-thumb"><img src="<?php echo $thumb_url; ?>" alt=""></div>
                            </a>
                            <?php endif; ?>
                            <div class="post-card-body">
                                <div class="post-card-meta">
                                    <span class="post-card-date"><?php echo htmlspecialchars(date('Y.m.d', strtotime($post['date'])), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($cat_name): ?>
                                        <span class="post-card-cat"><?php echo htmlspecialchars($cat_name, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h2 class="post-card-title">
                                    <a href="index.php?slug=<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h2>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="posts-empty">該当する記事はありません。</p>
                <?php endif; ?>
            </div>
        </div>
        <?php include __DIR__ . '/' . $theme_dir . '/sidebar.php'; ?>
    </div>
</main>

<?php include __DIR__ . '/' . $theme_dir . '/footer.php'; ?>