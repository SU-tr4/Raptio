<?php
// admin/editor.php
require_once __DIR__ . '/config.php';
session_start();

if (!isset($_SESSION['raptio_auth'])) {
    header('Location: index.php');
    exit;
}

// カテゴリーとメディアデータの読み込み
$categories_file = DATA_DIR . '/categories.json';
$all_categories = file_exists($categories_file) ? json_decode(file_get_contents($categories_file), true) : [];

// アップロード済みメディア一覧の取得（モーダル用）
$upload_dir = __DIR__ . '/../uploads/';
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
$media_files = [];
if (file_exists($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_extensions, true)) {
                $file_path = $upload_dir . $file;
                $media_files[] = [
                    'name' => $file,
                    'url'  => '../uploads/' . $file,
                    'date' => date('Y-m-d H:i', filemtime($file_path)),
                    'size' => round(filesize($file_path) / 1024, 1) . ' KB'
                ];
            }
        }
    }
    usort($media_files, function($a, $b) { return strcmp($b['date'], $a['date']); });
}

$id = $_GET['id'] ?? '';
$title = '';
$slug = '';
$content = '';
$status = 'draft';
$date = '';
$category_id = '';
$thumbnail = '';

if ($id) {
    $index_data = json_decode(file_get_contents(INDEX_FILE), true) ?? [];
    foreach ($index_data as $post) {
        if ($post['id'] === $id) {
            $title      = $post['title'];
            $slug       = $post['slug'];
            $status     = $post['status'];
            $date       = $post['date'];
            $category_id = $post['category_id'] ?? '';
            $thumbnail  = $post['thumbnail'] ?? '';
            $full_path  = __DIR__ . '/../' . $post['file_path'];
            if (file_exists($full_path)) {
                $content = file_get_contents($full_path);
            }
            break;
        }
    }
}

$page_title  = $id ? '投稿の編集' : '新規投稿を追加';
$current_page = 'editor';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="wp-admin-header">
    <h2><?php echo $id ? '投稿の編集' : '新規投稿を追加'; ?></h2>
</div>

<form id="editorForm">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="editor-layout">
        <div class="editor-left">
            <div class="form-group-title">
                <input type="text" name="title" value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" placeholder="タイトルを追加" required class="editor-title-input">
            </div>
            <div class="form-group-content">
                <textarea name="content" placeholder="ここに文章を入力してください（マークダウン対応）" required class="editor-textarea"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>

        <div class="editor-right">
            <div class="postbox">
                <div class="postbox-title">公開設定</div>
                <div class="postbox-inside">
                    <div class="form-group">
                        <label for="status">ステータス</label>
                        <select id="status" name="status">
                            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>下書き</option>
                            <option value="public" <?php echo $status === 'public' ? 'selected' : ''; ?>>公開</option>
                        </select>
                    </div>
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
                    <div class="form-group">
                        <label for="slug">スラッグ</label>
                        <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" placeholder="post-slug" required>
                    </div>
                </div>
            </div>

            <!-- アイキャッチ画像ボックス -->
            <div class="postbox">
                <div class="postbox-title">アイキャッチ画像</div>
                <div class="postbox-inside">
                    <input type="hidden" name="thumbnail" id="thumbnail-input" value="<?php echo htmlspecialchars($thumbnail, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="file" name="thumb_file" id="thumb_file" style="display:none;" accept="image/*">

                    <!-- プレビューエリア -->
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

                    <!-- ボタン群 -->
                    <div class="thumb-btn-group">
                        <button type="button" class="button button-secondary thumb-select-btn" onclick="openMediaModal()">
                            <?php echo $thumbnail ? '画像を変更' : 'アイキャッチ画像を設定'; ?>
                        </button>
                        <?php if ($thumbnail): ?>
                            <button type="button" class="button-link-delete" id="thumb-remove-link" onclick="removeThumbnail()">アイキャッチ画像を削除</button>
                        <?php else: ?>
                            <button type="button" class="button-link-delete" id="thumb-remove-link" onclick="removeThumbnail()" style="display:none;">アイキャッチ画像を削除</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="postbox-submit-area">
                <button type="submit" class="button button-primary" style="width:100%;">公開・保存</button>
            </div>
        </div>
    </div>
</form>

<!-- ============================================================
     メディア選択モーダル
     ============================================================ -->
<div id="media-modal-overlay" class="media-modal-overlay" onclick="closeMediaModalOnOverlay(event)">
    <div class="media-modal">

        <!-- ヘッダー -->
        <div class="media-modal-header">
            <div class="media-modal-tabs">
                <button type="button" class="media-tab active" data-tab="upload" onclick="switchTab('upload')">ファイルをアップロード</button>
                <button type="button" class="media-tab" data-tab="library" onclick="switchTab('library')">メディアライブラリ</button>
            </div>
            <button type="button" class="media-modal-close" onclick="closeMediaModal()">&times;</button>
        </div>

        <!-- アップロードタブ -->
        <div id="tab-upload" class="media-tab-content active">
            <div id="upload-dropzone" class="upload-dropzone">
                <div class="dropzone-inner">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <p class="dropzone-title">ここにファイルをドロップ</p>
                    <p class="dropzone-sub">または</p>
                    <button type="button" class="button button-primary" onclick="document.getElementById('modal-file-input').click()">ファイルを選択</button>
                    <p class="dropzone-note">JPG, PNG, GIF, WEBP, SVG に対応</p>
                </div>
                <div id="dropzone-uploading" class="dropzone-uploading" style="display:none;">
                    <div class="upload-spinner"></div>
                    <p>アップロード中...</p>
                </div>
            </div>
            <input type="file" id="modal-file-input" accept="image/*" multiple style="display:none;">
        </div>

        <!-- ライブラリタブ -->
        <div id="tab-library" class="media-tab-content">
            <div class="library-toolbar">
                <input type="text" id="library-search" class="library-search-input" placeholder="ファイル名で検索..." oninput="filterLibrary(this.value)">
                <span id="library-count" class="library-count"></span>
            </div>
            <div id="library-grid" class="library-grid">
                <?php if (empty($media_files)): ?>
                    <div class="library-empty">まだ画像がアップロードされていません。</div>
                <?php else: ?>
                    <?php foreach ($media_files as $item): ?>
                        <div class="library-item"
                             data-url="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"
                             data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                             data-size="<?php echo htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8'); ?>"
                             onclick="selectLibraryItem(this)">
                            <div class="library-item-img-wrap">
                                <img src="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                                <div class="library-item-check"><svg viewBox="0 0 12 10" width="12" height="10"><polyline points="1,5 4.5,9 11,1" stroke="#fff" stroke-width="2" fill="none"/></svg></div>
                            </div>
                            <p class="library-item-name"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- フッター -->
        <div class="media-modal-footer">
            <div id="selected-info" class="selected-info"></div>
            <div class="media-modal-footer-btns">
                <button type="button" class="button button-secondary" onclick="closeMediaModal()">キャンセル</button>
                <button type="button" class="button button-primary" id="btn-select-image" onclick="applySelectedImage()" disabled>画像を設定</button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================
// サムネイル関連
// ============================================================
function removeThumbnail() {
    document.getElementById('thumbnail-input').value = '';
    const preview = document.getElementById('thumb-preview');
    preview.classList.remove('has-image');
    preview.innerHTML = `
        <div class="thumb-empty-state">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            <p>画像が設定されていません</p>
        </div>`;
    const btn = document.querySelector('.thumb-select-btn');
    if (btn) btn.textContent = 'アイキャッチ画像を設定';
    const removeLink = document.getElementById('thumb-remove-link');
    if (removeLink) removeLink.style.display = 'none';
}

function applyThumbnail(url, isDataUrl) {
    document.getElementById('thumbnail-input').value = isDataUrl ? '' : url.replace('../', '');
    const preview = document.getElementById('thumb-preview');
    preview.classList.add('has-image');
    preview.innerHTML = `<img src="${url}" alt="アイキャッチ画像"><button type="button" class="thumb-remove-btn" onclick="removeThumbnail()" title="削除">&times;</button>`;
    const btn = document.querySelector('.thumb-select-btn');
    if (btn) btn.textContent = '画像を変更';
    const removeLink = document.getElementById('thumb-remove-link');
    if (removeLink) removeLink.style.display = '';
}

// ============================================================
// メディアモーダル
// ============================================================
let selectedLibraryItem = null;
let pendingUploadFile  = null;

function openMediaModal() {
    selectedLibraryItem = null;
    pendingUploadFile   = null;
    updateFooter();
    document.getElementById('media-modal-overlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMediaModal() {
    document.getElementById('media-modal-overlay').classList.remove('active');
    document.body.style.overflow = '';
    selectedLibraryItem = null;
    pendingUploadFile   = null;
}

function closeMediaModalOnOverlay(e) {
    if (e.target === document.getElementById('media-modal-overlay')) closeMediaModal();
}

// タブ切り替え
function switchTab(tab) {
    document.querySelectorAll('.media-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.media-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelector(`.media-tab[data-tab="${tab}"]`).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
    selectedLibraryItem = null;
    pendingUploadFile   = null;
    updateFooter();
}

// ライブラリ：アイテム選択
function selectLibraryItem(el) {
    document.querySelectorAll('.library-item').forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
    selectedLibraryItem = { url: el.dataset.url, name: el.dataset.name, size: el.dataset.size };
    updateFooter();
}

// ライブラリ検索フィルター
function filterLibrary(q) {
    const items = document.querySelectorAll('.library-item');
    const lower = q.toLowerCase();
    let visible = 0;
    items.forEach(item => {
        const match = item.dataset.name.toLowerCase().includes(lower);
        item.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const cnt = document.getElementById('library-count');
    if (cnt) cnt.textContent = q ? `${visible} 件が一致` : '';
}

// フッター更新
function updateFooter() {
    const info = document.getElementById('selected-info');
    const btn  = document.getElementById('btn-select-image');
    if (selectedLibraryItem) {
        info.textContent = `選択中: ${selectedLibraryItem.name}（${selectedLibraryItem.size}）`;
        btn.disabled = false;
    } else if (pendingUploadFile) {
        info.textContent = `アップロード予定: ${pendingUploadFile.name}`;
        btn.disabled = false;
    } else {
        info.textContent = '';
        btn.disabled = true;
    }
}

// 決定ボタン
function applySelectedImage() {
    if (selectedLibraryItem) {
        applyThumbnail(selectedLibraryItem.url, false);
        closeMediaModal();
    } else if (pendingUploadFile) {
        uploadAndApply(pendingUploadFile);
    }
}

// ============================================================
// ファイルアップロード処理
// ============================================================
async function uploadAndApply(file) {
    const dropzone  = document.getElementById('upload-dropzone');
    const uploading = document.getElementById('dropzone-uploading');
    const inner     = dropzone.querySelector('.dropzone-inner');
    inner.style.display     = 'none';
    uploading.style.display = 'flex';

    const formData = new FormData();
    formData.append('async_upload', file);

    try {
        // media.php のアップロードロジックを流用（直接 POST）
        const res = await fetch('api.php?action=upload_media', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success && data.url) {
            applyThumbnail('../' + data.url, false);
            // ライブラリへもアイテムを動的追加
            addToLibraryGrid(data.url, data.name, data.size);
        } else {
            // フォールバック：Data URLとして即時プレビュー（ファイルは thumb_file として送信）
            const reader = new FileReader();
            reader.onload = e => applyThumbnail(e.target.result, true);
            reader.readAsDataURL(file);
            // thumb_file input に割り当て
            assignFileToHiddenInput(file);
        }
    } catch {
        // fetch 失敗時もフォールバック
        const reader = new FileReader();
        reader.onload = e => applyThumbnail(e.target.result, true);
        reader.readAsDataURL(file);
        assignFileToHiddenInput(file);
    }

    inner.style.display     = '';
    uploading.style.display = 'none';
    closeMediaModal();
}

// DataTransfer を使って thumb_file input にファイルをセット
function assignFileToHiddenInput(file) {
    try {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('thumb_file').files = dt.files;
    } catch(e) {}
}

// ライブラリグリッドに動的追加
function addToLibraryGrid(url, name, size) {
    const grid = document.getElementById('library-grid');
    const emptyEl = grid.querySelector('.library-empty');
    if (emptyEl) emptyEl.remove();

    const el = document.createElement('div');
    el.className   = 'library-item';
    el.dataset.url  = '../' + url;
    el.dataset.name = name;
    el.dataset.size = size;
    el.onclick = function() { selectLibraryItem(this); };
    el.innerHTML = `
        <div class="library-item-img-wrap">
            <img src="../${url}" alt="${name}" loading="lazy">
            <div class="library-item-check"><svg viewBox="0 0 12 10" width="12" height="10"><polyline points="1,5 4.5,9 11,1" stroke="#fff" stroke-width="2" fill="none"/></svg></div>
        </div>
        <p class="library-item-name">${name}</p>`;
    grid.prepend(el);
}

// ============================================================
// ドラッグ＆ドロップ
// ============================================================
const dropzone = document.getElementById('upload-dropzone');

['dragenter', 'dragover'].forEach(ev => {
    dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
});
['dragleave', 'drop'].forEach(ev => {
    dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.remove('drag-over'); });
});
dropzone.addEventListener('drop', e => {
    const files = e.dataTransfer.files;
    if (files.length > 0) handleDropzoneFile(files[0]);
});

document.getElementById('modal-file-input').addEventListener('change', function() {
    if (this.files.length > 0) handleDropzoneFile(this.files[0]);
});

function handleDropzoneFile(file) {
    const allowed = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
    if (!allowed.includes(file.type)) {
        alert('対応していないファイル形式です。');
        return;
    }
    pendingUploadFile = file;

    // プレビュー表示
    const reader = new FileReader();
    reader.onload = e => {
        const inner = dropzone.querySelector('.dropzone-inner');
        inner.innerHTML = `
            <img src="${e.target.result}" style="max-height:180px; max-width:100%; border-radius:4px; margin-bottom:10px; box-shadow:0 2px 8px rgba(0,0,0,0.15);">
            <p style="font-size:13px; font-weight:600; margin:0 0 4px;">${file.name}</p>
            <p style="font-size:12px; color:#646970; margin:0;">${(file.size/1024).toFixed(1)} KB</p>
            <button type="button" class="button button-secondary" style="margin-top:12px;" onclick="resetDropzone()">別のファイルを選択</button>`;
        updateFooter();
    };
    reader.readAsDataURL(file);
}

function resetDropzone() {
    pendingUploadFile = null;
    const inner = dropzone.querySelector('.dropzone-inner');
    inner.innerHTML = `
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        <p class="dropzone-title">ここにファイルをドロップ</p>
        <p class="dropzone-sub">または</p>
        <button type="button" class="button button-primary" onclick="document.getElementById('modal-file-input').click()">ファイルを選択</button>
        <p class="dropzone-note">JPG, PNG, GIF, WEBP, SVG に対応</p>`;
    updateFooter();
}

// ESC で閉じる
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.getElementById('media-modal-overlay').classList.contains('active')) {
        closeMediaModal();
    }
});

// ============================================================
// フォーム送信
// ============================================================
document.getElementById('editorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) { alert('保存しました'); window.location.href = 'index.php'; }
            else { alert('エラー: ' + (data.error || '不明なエラー')); }
        });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>