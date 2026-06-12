<div class="editor-right">
            <div class="postbox">
                <div class="postbox-title">公開設定</div>
                <div class="postbox-inside">
                    <div class="form-group">
                        <label for="status">ステータス</label>
                        <select id="status" name="status">
                            <option value="draft"  <?php echo $status === 'draft'  ? 'selected' : ''; ?>>下書き</option>
                            <option value="public" <?php echo $status === 'public' ? 'selected' : ''; ?>>公開</option>
                        </select>
                    </div>
                    <?php if ($mode === 'post'): ?>
                    <div class="form-group">
                        <label for="category_id">カテゴリー</label>
                        <select id="category_id" name="category_id">
                            <option value="">未分類</option>
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $category_id === $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="slug">スラッグ</label>
                        <input type="text" id="slug" name="slug"
                               value="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="<?php echo $mode === 'page' ? 'page-slug' : 'post-slug'; ?>">
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-title">アイキャッチ画像</div>
                <div class="postbox-inside">
                    <input type="hidden" name="thumbnail" id="thumbnail-input" value="<?php echo htmlspecialchars($thumbnail, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="file" name="thumb_file" id="thumb_file" style="display:none;" accept="image/*">

                    <div id="thumb-preview" class="thumb-preview-area <?php echo $thumbnail ? 'has-image' : ''; ?>">
                        <?php if ($thumbnail): ?>
                            <img src="../<?php echo htmlspecialchars($thumbnail, ENT_QUOTES, 'UTF-8'); ?>" alt="アイキャッチ画像">
                            <button type="button" class="thumb-remove-btn" onclick="removeThumbnail()" title="削除">&times;</button>
                        <?php else: ?>
                            <div class="thumb-empty-state">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                <p>画像が設定されていません</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="thumb-btn-group">
                        <button type="button" class="button button-secondary thumb-select-btn" onclick="openMediaModal()">
                            <?php echo $thumbnail ? '画像を変更' : 'アイキャッチ画像を設定'; ?>
                        </button>
                        <button type="button" class="button-link-delete" id="thumb-remove-link" onclick="removeThumbnail()" <?php echo $thumbnail ? '' : 'style="display:none;"'; ?>>アイキャッチ画像を削除</button>
                    </div>
                </div>
            </div>

            <div class="postbox-submit-area">
                <button type="submit" class="button button-primary" style="width:100%;">公開・保存</button>
            </div>
        </div>
    </div>