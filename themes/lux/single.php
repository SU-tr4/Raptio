<?php
// カテゴリーマップの構築
$categories_file = SITE_ROOT . '/data/categories.json';
$all_categories  = file_exists($categories_file) ? json_decode(file_get_contents($categories_file), true) : [];
$all_categories  = is_array($all_categories) ? $all_categories : [];
$cat_map = [];
foreach ($all_categories as $cat) { $cat_map[$cat['id']] = $cat['name']; }

// 記事メタ情報の取得
$page_title_val = $slug; // デフォルトはスラッグ
$page_date   = '';
$page_cat_id = '';
$page_thumb  = '';
foreach ($posts as $post) {
    if ($post['slug'] === $slug) {
        $page_title_val = $post['title']; // 記事タイトルを取得
        $page_date   = $post['date'];
        $page_cat_id = $post['category_id'] ?? '';
        $page_thumb  = $post['thumbnail'] ?? '';
        break;
    }
}

// ページタイトル設定（記事タイトル取得後に定義）
$page_title = $page_title_val . ' - ' . $site_config['site_name'];

// ヘッダー読み込み
include 'header.php';

// 残りの変数の準備
$page_cat_name  = $page_cat_id ? ($cat_map[$page_cat_id] ?? '') : '';
$has_thumb      = !empty($page_thumb) && file_exists(SITE_ROOT . '/' . $page_thumb);
?>

    <main class="container" style="margin-top: 40px;">
        <div class="layout-wrapper">

            <div class="main-content">
                <div class="breadcrumb">
                    <a href="index.php">&larr; 一覧に戻る</a>
                    <?php if ($page_cat_name): ?>
                        <span class="breadcrumb-sep">/</span>
                        <a href="index.php?cat=<?php echo htmlspecialchars($page_cat_id, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($page_cat_name, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endif; ?>
                </div>

                <article class="article-window">

                    <?php if ($has_thumb): ?>
                    <div class="article-eyecatch">
                        <img src="<?php echo htmlspecialchars($page_thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($page_title_val, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <?php endif; ?>

                    <header class="article-header">
                        <div class="article-meta">
                            <?php if ($page_cat_name): ?>
                                <a href="index.php?cat=<?php echo htmlspecialchars($page_cat_id, ENT_QUOTES, 'UTF-8'); ?>" class="article-cat-badge"><?php echo htmlspecialchars($page_cat_name, ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php endif; ?>
                            <?php if ($page_date): ?>
                                <span class="article-date">Released on <?php echo htmlspecialchars(date('Y.m.d', strtotime($page_date)), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                        <h1 class="article-title"><?php echo htmlspecialchars($page_title_val, ENT_QUOTES, 'UTF-8'); ?></h1>
                    </header>

                    <div class="article-content markdown-body">
                        <?php echo $content; ?>
                    </div>

                </article>
            </div>

            <?php include 'sidebar.php'; ?>
        </div>
    </main>

<?php include 'footer.php'; ?>