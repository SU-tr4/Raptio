<?php
$page_title = $site_config['site_name'] . ' - Updates';
include 'header.php';
?>

    <section class="cms-hero">
        <div class="hero-bg-glow"></div>
        <div class="container">
            <h1 class="mock-hero-title" style="font-size: 3.5rem; font-weight: 700; margin-bottom: 15px; letter-spacing: -0.02em;">
                <span class="text-gradient"><?php echo htmlspecialchars($site_config['site_name'], ENT_QUOTES, 'UTF-8'); ?></span> Updates
            </h1>
            <p style="font-size: 1.2rem; color: var(--text-sub); max-width: 600px; margin: 0 auto; line-height: 1.7;">
                <?php echo htmlspecialchars($site_config['site_description'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
    </section>

    <?php
    $categories_file = SITE_ROOT . '/data/categories.json';
    $all_categories  = file_exists($categories_file) ? json_decode(file_get_contents($categories_file), true) : [];
    $all_categories  = is_array($all_categories) ? $all_categories : [];

    $cat_map = [];
    foreach ($all_categories as $cat) { $cat_map[$cat['id']] = $cat['name']; }

    // index.phpではフィルタリングを行わず全件表示とするため、フィルター変数は空にします
    $filter_cat = '';

    $public_posts = [];
    foreach ($posts as $post) {
        if (($post['status'] ?? '') !== 'public') continue;
        $public_posts[] = $post;
    }
    ?>

    <main class="container" style="margin-top: 40px;">

        <?php if (!empty($all_categories)): ?>
        <div class="category-filter-bar">
            <a href="index.php" class="cat-filter-btn active">すべて</a>
            <?php foreach ($all_categories as $cat): ?>
                <a href="archive.php?cat=<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="cat-filter-btn">
                    <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="layout-wrapper">
            <div class="main-content">
                <div class="posts-list-stack">
                    <?php if (!empty($public_posts)): ?>
                        <?php foreach ($public_posts as $post): ?>
                            <?php
                            $has_thumb   = !empty($post['thumbnail']) && file_exists(SITE_ROOT . '/' . $post['thumbnail']);
                            $thumb_url   = $has_thumb ? htmlspecialchars($post['thumbnail'], ENT_QUOTES, 'UTF-8') : '';
                            $cat_name    = !empty($post['category_id']) ? ($cat_map[$post['category_id']] ?? '') : '';
                            ?>
                            <article class="post-card <?php echo $has_thumb ? 'post-card--has-thumb' : ''; ?>">

                                <?php if ($has_thumb): ?>
                                <a href="index.php?slug=<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="post-card-thumb-link">
                                    <div class="post-card-thumb">
                                        <img src="<?php echo $thumb_url; ?>" alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </a>
                                <?php endif; ?>

                                <div class="post-card-body">
                                    <div class="post-card-meta">
                                        <span class="post-card-date"><?php echo htmlspecialchars(date('Y.m.d', strtotime($post['date'])), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($cat_name): ?>
                                            <a href="archive.php?cat=<?php echo htmlspecialchars($post['category_id'], ENT_QUOTES, 'UTF-8'); ?>" class="post-card-cat"><?php echo htmlspecialchars($cat_name, ENT_QUOTES, 'UTF-8'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <h2 class="post-card-title">
                                        <a href="index.php?slug=<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </h2>
                                    <p class="post-card-excerpt">詳細な情報を確認するには、タイトルをクリックしてください。</p>
                                </div>

                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="posts-empty">
                            現在、公開されている記事はありません。
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php include 'sidebar.php'; ?>
        </div>
    </main>

<?php include 'footer.php'; ?>