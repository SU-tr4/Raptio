<?php
global $post_meta, $content;
get_template_part('header');
?>
        <main>
            <article>
                <?php if (!empty($post_meta['thumbnail'])) : ?>
                    <div class="post-thumbnail" style="margin-bottom: 32px;">
                        <img src="<?php echo htmlspecialchars($post_meta['thumbnail']); ?>" alt="">
                    </div>
                <?php endif; ?>

                <?php if (!empty($post_meta['category'])) : ?>
                    <p class="post-meta"><?php echo htmlspecialchars($post_meta['category']); ?></p>
                <?php endif; ?>

                <h1><?php echo htmlspecialchars($post_meta['title']); ?></h1>

                <div class="content">
                    <?php echo $content; ?>
                </div>

                <a href="index.php" class="back-link">記事一覧に戻る</a>
            </article>
        </main>
<?php
get_template_part('sidebar');
get_template_part('footer');
?>