<?php
global $posts;
get_template_part('header');
?>
        <main>
            <p class="section-label">最新の記事</p>

            <?php if (!empty($posts)) : ?>
                <?php foreach ($posts as $post) : ?>
                    <?php if (($post['status'] ?? '') === 'public') : ?>
                        <article>
                            <?php if (!empty($post['thumbnail'])) : ?>
                                <div class="post-thumbnail">
                                    <a href="?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                        <img src="<?php echo htmlspecialchars($post['thumbnail']); ?>" alt="">
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($post['category'])) : ?>
                                <p class="post-meta"><?php echo htmlspecialchars($post['category']); ?></p>
                            <?php endif; ?>

                            <h2>
                                <a href="?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>

                            <?php if (!empty($post['excerpt'])) : ?>
                                <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                            <?php endif; ?>

                            <a class="read-more" href="?slug=<?php echo htmlspecialchars($post['slug']); ?>">続きを読む</a>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="empty-state">公開記事がありません。</p>
            <?php endif; ?>
        </main>
<?php
get_template_part('sidebar');
get_template_part('footer');
?>