<?php
global $post_meta, $content;
get_template_part('header');
?>
<main>
    <article>
        <?php if (!empty($post_meta['thumbnail'])) : ?>
            <div class="post-thumbnail" style="margin-bottom: 2.5rem;">
                <img src="<?php echo htmlspecialchars($post_meta['thumbnail']); ?>" alt="">
            </div>
        <?php endif; ?>

        <h1><?php echo htmlspecialchars($post_meta['title']); ?></h1>

        <div class="content">
            <?php echo $content; ?>
        </div>

        <a href="index.php" class="back-link">記事一覧に戻る</a>
    </article>
</main>
<?php get_template_part('footer'); ?>