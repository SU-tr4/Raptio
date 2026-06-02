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
            $title       = $post['title'];
            $slug        = $post['slug'];
            $status      = $post['status'];
            $date        = $post['date'];
            $category_id = $post['category_id'] ?? '';
            $thumbnail   = $post['thumbnail'] ?? '';
            $full_path   = __DIR__ . '/../' . $post['file_path'];
            if (file_exists($full_path)) {
                $content = file_get_contents($full_path);
            }
            break;
        }
    }
}

$page_title  = $id ? '投稿の編集' : '新規投稿を追加';
$current_page = 'posts';
$sub_page = 'add';

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

            <!-- エディタモード切り替えタブ -->
            <div class="editor-mode-bar">
                <div class="editor-mode-tabs">
                    <button type="button" class="editor-mode-tab active" data-mode="markdown" onclick="switchEditorMode('markdown')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Markdown
                    </button>
                    <button type="button" class="editor-mode-tab" data-mode="richtext" onclick="switchEditorMode('richtext')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        リッチテキスト
                    </button>
                </div>
                <span class="editor-mode-notice" id="editorModeNotice"></span>
            </div>

            <!-- マークダウンエディタ -->
            <div id="markdownEditorWrap" class="form-group-content editor-pane active">
                <div class="editor-toolbar" id="markdownToolbar">
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="見出し1" onclick="mdInsert('heading1')"><b>H1</b></button>
                        <button type="button" class="toolbar-btn" title="見出し2" onclick="mdInsert('heading2')"><b>H2</b></button>
                        <button type="button" class="toolbar-btn" title="見出し3" onclick="mdInsert('heading3')"><b>H3</b></button>
                    </div>
                    <div class="toolbar-sep"></div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="太字 (Ctrl+B)" onclick="mdInsert('bold')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg></button>
                        <button type="button" class="toolbar-btn" title="斜体 (Ctrl+I)" onclick="mdInsert('italic')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg></button>
                        <button type="button" class="toolbar-btn" title="取り消し線" onclick="mdInsert('strike')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><path d="M16 6C16 6 14.5 4 12 4C9.5 4 7 5.5 7 8C7 10.5 9 11.5 12 12"/><path d="M8 18C8 18 9.5 20 12 20C14.5 20 17 18.5 17 16C17 13.5 15 12.5 12 12"/></svg></button>
                    </div>
                    <div class="toolbar-sep"></div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="リンク" onclick="mdInsert('link')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
                        <button type="button" class="toolbar-btn" title="画像" onclick="mdInsert('image')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></button>
                    </div>
                    <div class="toolbar-sep"></div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="順序なしリスト" onclick="mdInsert('ul')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg></button>
                        <button type="button" class="toolbar-btn" title="順序ありリスト" onclick="mdInsert('ol')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg></button>
                        <button type="button" class="toolbar-btn" title="引用" onclick="mdInsert('blockquote')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 2v6c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 2v6c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg></button>
                        <button type="button" class="toolbar-btn" title="コード" onclick="mdInsert('code')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></button>
                        <button type="button" class="toolbar-btn" title="コードブロック" onclick="mdInsert('codeblock')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="18" rx="2"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="8" x2="12" y2="8"/><line x1="8" y1="16" x2="14" y2="16"/></svg></button>
                    </div>
                    <div class="toolbar-sep"></div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="水平線" onclick="mdInsert('hr')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
                    </div>
                </div>
                <textarea id="markdownTextarea" name="content" placeholder="ここに文章を入力してください（マークダウン対応）" required class="editor-textarea"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <!-- リッチテキストエディタ -->
            <div id="richtextEditorWrap" class="form-group-content editor-pane" style="display:none;">
                <div class="editor-toolbar" id="richtextToolbar">
                    <div class="toolbar-group">
                        <select class="toolbar-select" onchange="rtExecBlock(this.value); this.value=''" title="段落スタイル">
                            <option value="">段落スタイル</option>
                            <option value="p">本文</option>
                            <option value="h1">見出し1</option>
                            <option value="h2">見出し2</option>
                            <option value="h3">見出し3</option>
                            <option value="h4">見出し4</option>
                            <option value="pre">コード</option>
                        </select>
                    </div>
                    <div class="toolbar-sep"></div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="太字 (Ctrl+B)" onclick="rtExec('bold')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg></button>
                        <button type="button" class="toolbar-btn" title="斜体 (Ctrl+I)" onclick="rtExec('italic')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg></button>
                        <button type="button" class="toolbar-btn" title="下線 (Ctrl+U)" onclick="rtExec('underline')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg></button>
                        <button type="button" class="toolbar-btn" title="取り消し線" onclick="rtExec('strikeThrough')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><path d="M16 6C16 6 14.5 4 12 4C9.5 4 7 5.5 7 8C7 10.5 9 11.5 12 12"/><path d="M8 18C8 18 9.5 20 12 20C14.5 20 17 18.5 17 16C17 13.5 15 12.5 12 12"/></svg></button>
                    </div>
                    <div class="toolbar-sep"></div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="左揃え" onclick="rtExec('justifyLeft')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg></button>
                        <button type="button" class="toolbar-btn" title="中央揃え" onclick="rtExec('justifyCenter')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg></button>
                        <button type="button" class="toolbar-btn" title="右揃え" onclick="rtExec('justifyRight')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg></button>
                    </div>
                    <div class="toolbar-sep"></div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="箇条書き" onclick="rtExec('insertUnorderedList')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg></button>
                        <button type="button" class="toolbar-btn" title="番号リスト" onclick="rtExec('insertOrderedList')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg></button>
                        <button type="button" class="toolbar-btn" title="インデント増" onclick="rtExec('indent')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/><path d="M7 12l4 4 4-4"/><line x1="11" y1="8" x2="11" y2="16"/></svg></button>
                        <button type="button" class="toolbar-btn" title="インデント減" onclick="rtExec('outdent')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/><path d="M7 8l4 4-4 4"/><line x1="11" y1="8" x2="11" y2="16"/></svg></button>
                    </div>
                    <div class="toolbar-sep"></div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="リンク挿入" onclick="rtInsertLink()"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
                        <button type="button" class="toolbar-btn" title="リンク解除" onclick="rtExec('unlink')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.84 12.25l1.72-1.71h-.02a5.004 5.004 0 0 0-.12-7.07 5.006 5.006 0 0 0-6.95 0l-1.72 1.71"/><path d="M5.17 11.75l-1.71 1.71a5.004 5.004 0 0 0 .12 7.07 5.006 5.006 0 0 0 6.95 0l1.71-1.71"/><line x1="8" y1="2" x2="8" y2="5"/><line x1="2" y1="8" x2="5" y2="8"/><line x1="16" y1="19" x2="16" y2="22"/><line x1="19" y1="16" x2="22" y2="16"/></svg></button>
                        <button type="button" class="toolbar-btn" title="画像挿入" onclick="rtInsertImage()"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></button>
                    </div>
                    <div class="toolbar-sep"></div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="元に戻す (Ctrl+Z)" onclick="rtExec('undo')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg></button>
                        <button type="button" class="toolbar-btn" title="やり直す (Ctrl+Y)" onclick="rtExec('redo')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg></button>
                    </div>
                </div>
                <div id="richtextArea" class="editor-richtext" contenteditable="true"></div>
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

<div id="media-modal-overlay" class="media-modal-overlay" onclick="closeMediaModalOnOverlay(event)">
    <div class="media-modal">

        <div class="media-modal-header">
            <div class="media-modal-tabs">
                <button type="button" class="media-tab active" data-tab="upload" onclick="switchTab('upload')">ファイルをアップロード</button>
                <button type="button" class="media-tab" data-tab="library" onclick="switchTab('library')">メディアライブラリ</button>
            </div>
            <button type="button" class="media-modal-close" onclick="closeMediaModal()">&times;</button>
        </div>

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
// ユーティリティ
// ============================================================
function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

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
// リッチテキストから画像/リンク挿入時に使うコールバック
let _rtMediaCallback = null;

function openMediaModal(callback) {
    selectedLibraryItem = null;
    pendingUploadFile   = null;
    _rtMediaCallback = callback || null;
    updateFooter();
    document.getElementById('media-modal-overlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMediaModal() {
    document.getElementById('media-modal-overlay').classList.remove('active');
    document.body.style.overflow = '';
    selectedLibraryItem = null;
    pendingUploadFile   = null;
    _rtMediaCallback = null;
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
        if (_rtMediaCallback) {
            _rtMediaCallback(selectedLibraryItem.url);
            closeMediaModal();
        } else {
            applyThumbnail(selectedLibraryItem.url, false);
            closeMediaModal();
        }
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
    inner.style.display       = 'none';
    uploading.style.display = 'flex';

    const formData = new FormData();
    formData.append('async_upload', file);

    try {
        const res = await fetch('api.php?action=upload_media', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success && data.url) {
            if (_rtMediaCallback) {
                _rtMediaCallback('../' + data.url);
            } else {
                applyThumbnail('../' + data.url, false);
            }
            addToLibraryGrid(data.url, data.name, data.size);
        } else {
            const reader = new FileReader();
            reader.onload = e => {
                if (_rtMediaCallback) {
                    _rtMediaCallback(e.target.result);
                } else {
                    applyThumbnail(e.target.result, true);
                }
            };
            reader.readAsDataURL(file);
            if (!_rtMediaCallback) assignFileToHiddenInput(file);
        }
    } catch {
        const reader = new FileReader();
        reader.onload = e => {
            if (_rtMediaCallback) {
                _rtMediaCallback(e.target.result);
            } else {
                applyThumbnail(e.target.result, true);
            }
        };
        reader.readAsDataURL(file);
        if (!_rtMediaCallback) assignFileToHiddenInput(file);
    }

    inner.style.display       = '';
    uploading.style.display = 'none';
    closeMediaModal();
}

function assignFileToHiddenInput(file) {
    try {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('thumb_file').files = dt.files;
    } catch(e) {}
}

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
// エディタモード切り替え（Markdown ⇔ リッチテキスト）
// ============================================================
let currentEditorMode = 'markdown';

// ----------------------------------------------------------------
// Markdown → HTML
// ----------------------------------------------------------------
function markdownToHtml(md) {
    if (!md || !md.trim()) return '<p><br></p>';

    // コードブロックを先に退避（内部をエスケープして保護）
    const codeBlocks = [];
    md = md.replace(/^```(\w*)\n?([\s\S]*?)^```/gm, (_, lang, code) => {
        codeBlocks.push(`<pre><code>${escHtml(code.replace(/\n$/, ''))}</code></pre>`);
        return `\x00CB${codeBlocks.length - 1}\x00`;
    });

    // インラインコードを退避
    const inlineCodes = [];
    md = md.replace(/`([^`\n]+)`/g, (_, c) => {
        inlineCodes.push(`<code>${escHtml(c)}</code>`);
        return `\x00IC${inlineCodes.length - 1}\x00`;
    });

    // 空行で段落を分割
    const blocks = md.split(/\n{2,}/);
    const htmlParts = blocks.map(block => {
        const trimmed = block.trim();
        if (!trimmed) return '';

        // コードブロックプレースホルダー
        if (/^\x00CB\d+\x00$/.test(trimmed)) return trimmed;

        // 見出し
        const heading = trimmed.match(/^(#{1,6})\s+(.+)$/);
        if (heading) {
            const level = heading[1].length;
            return `<h${level}>${inlineToHtml(heading[2], inlineCodes)}</h${level}>`;
        }

        // 水平線
        if (/^[-*_]{3,}$/.test(trimmed)) return '<hr>';

        // 引用ブロック
        if (/^>/.test(trimmed)) {
            const content = trimmed.split('\n').map(l => l.replace(/^>\s?/, '')).join('\n');
            return `<blockquote>${inlineToHtml(content, inlineCodes)}</blockquote>`;
        }

        // 順序ありリスト
        if (/^\d+\.\s/.test(trimmed)) {
            const items = trimmed.split('\n')
                .filter(l => /^\d+\.\s/.test(l))
                .map(l => `<li>${inlineToHtml(l.replace(/^\d+\.\s+/, ''), inlineCodes)}</li>`)
                .join('');
            return `<ol>${items}</ol>`;
        }

        // 順序なしリスト
        if (/^[-*+]\s/.test(trimmed)) {
            const items = trimmed.split('\n')
                .filter(l => /^[-*+]\s/.test(l))
                .map(l => `<li>${inlineToHtml(l.replace(/^[-*+]\s+/, ''), inlineCodes)}</li>`)
                .join('');
            return `<ul>${items}</ul>`;
        }

        // 段落（単一行の場合はbrなし、複数行はbr区切り）
        const lines = trimmed.split('\n');
        const content = lines.map(l => inlineToHtml(l, inlineCodes)).join('<br>');
        return `<p>${content}</p>`;
    });

    let html = htmlParts.join('\n');

    // プレースホルダーを戻す
    html = html.replace(/\x00CB(\d+)\x00/g, (_, i) => codeBlocks[+i]);
    html = html.replace(/\x00IC(\d+)\x00/g, (_, i) => inlineCodes[+i]);

    return html || '<p><br></p>';
}

// インライン要素の変換
function inlineToHtml(text, inlineCodes) {
    // インラインコードプレースホルダーはそのまま通す
    // 画像（リンクより先）
    text = text.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img src="$2" alt="$1">');
    // リンク
    text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');
    // 太字+斜体
    text = text.replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>');
    // 太字
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/__(.+?)__/g, '<strong>$1</strong>');
    // 斜体
    text = text.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
    text = text.replace(/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/g, '<em>$1</em>');
    // 取り消し線
    text = text.replace(/~~(.+?)~~/g, '<del>$1</del>');
    // インラインコード戻し
    if (inlineCodes) {
        text = text.replace(/\x00IC(\d+)\x00/g, (_, i) => inlineCodes[+i]);
    }
    return text;
}

// ----------------------------------------------------------------
// HTML → Markdown（execCommandが生成する汚いHTMLを完全に正規化）
// ----------------------------------------------------------------
function htmlToMarkdown(html) {
    if (!html || !html.trim()) return '';

    const div = document.createElement('div');
    div.innerHTML = html;

    // Step1: DOMを正規化（execCommandが挿入するゴミ属性・タグを整理）
    normalizeEditableHtml(div);

    // Step2: DOM → Markdown テキスト変換
    const md = domToMarkdown(div).trim();
    // 3連続以上の空行を2行に
    return md.replace(/\n{3,}/g, '\n\n');
}

// execCommand由来のゴミHTML正規化
function normalizeEditableHtml(root) {
    // style付きspanをアンラップ or セマンティックタグに変換
    root.querySelectorAll('span').forEach(el => {
        const style = el.getAttribute('style') || '';
        const fw = el.style.fontWeight;
        const fs = el.style.fontStyle;
        const td = el.style.textDecoration;

        if (fw === 'bold' || fw === '700' || parseInt(fw) >= 700) {
            const strong = document.createElement('strong');
            while (el.firstChild) strong.appendChild(el.firstChild);
            el.parentNode.replaceChild(strong, el);
        } else if (fs === 'italic') {
            const em = document.createElement('em');
            while (el.firstChild) em.appendChild(el.firstChild);
            el.parentNode.replaceChild(em, el);
        } else if (td === 'line-through') {
            const del = document.createElement('del');
            while (el.firstChild) del.appendChild(el.firstChild);
            el.parentNode.replaceChild(del, el);
        } else if (td === 'underline') {
            const u = document.createElement('u');
            while (el.firstChild) u.appendChild(el.firstChild);
            el.parentNode.replaceChild(u, el);
        } else {
            // 装飾なしspanはアンラップ
            const frag = document.createDocumentFragment();
            while (el.firstChild) frag.appendChild(el.firstChild);
            el.parentNode.replaceChild(frag, el);
        }
    });

    // <font>タグをアンラップ
    root.querySelectorAll('font').forEach(el => {
        const frag = document.createDocumentFragment();
        while (el.firstChild) frag.appendChild(el.firstChild);
        el.parentNode.replaceChild(frag, el);
    });

    // styleのあるb/i/strong/emはそのままにして属性だけ削除
    root.querySelectorAll('b,i,strong,em,del,s,u,a,h1,h2,h3,h4,h5,h6,p,li,ul,ol,blockquote,pre,code').forEach(el => {
        // href以外のstyle属性を削除
        if (el.tagName.toLowerCase() !== 'a') {
            el.removeAttribute('style');
        }
        el.removeAttribute('class');
        el.removeAttribute('id');
    });
}

// DOM → Markdown 再帰変換
function domToMarkdown(node, ctx) {
    ctx = ctx || { listDepth: 0 };

    if (node.nodeType === Node.TEXT_NODE) {
        return node.textContent;
    }
    if (node.nodeType !== Node.ELEMENT_NODE) return '';

    const tag = node.tagName.toLowerCase();

    // 子ノードを再帰変換するヘルパー
    const innerMd = (extraCtx) => {
        const c = Object.assign({}, ctx, extraCtx || {});
        return Array.from(node.childNodes).map(n => domToMarkdown(n, c)).join('');
    };

    switch (tag) {
        case 'h1': return `# ${innerMd().trim()}\n\n`;
        case 'h2': return `## ${innerMd().trim()}\n\n`;
        case 'h3': return `### ${innerMd().trim()}\n\n`;
        case 'h4': return `#### ${innerMd().trim()}\n\n`;
        case 'h5': return `##### ${innerMd().trim()}\n\n`;
        case 'h6': return `###### ${innerMd().trim()}\n\n`;

        case 'strong': case 'b': {
            const t = innerMd().trim();
            return t ? `**${t}**` : '';
        }
        case 'em': case 'i': {
            const t = innerMd().trim();
            return t ? `*${t}*` : '';
        }
        case 'del': case 's': case 'strike': {
            const t = innerMd().trim();
            return t ? `~~${t}~~` : '';
        }
        case 'u': return innerMd(); // mdに相当なし、テキストとして保持

        case 'a': {
            const href = node.getAttribute('href') || '';
            const text = innerMd().trim();
            return text ? `[${text}](${href})` : href;
        }
        case 'img': {
            const src = node.getAttribute('src') || '';
            const alt = node.getAttribute('alt') || '';
            return `![${alt}](${src})`;
        }

        case 'code': {
            // preの中のcodeはpreで処理する
            if (node.parentElement && node.parentElement.tagName.toLowerCase() === 'pre') {
                return node.textContent;
            }
            return `\`${node.textContent}\``;
        }
        case 'pre': {
            const codeEl = node.querySelector('code');
            const codeText = (codeEl ? codeEl.textContent : node.textContent);
            return `\`\`\`\n${codeText}\n\`\`\`\n\n`;
        }

        case 'blockquote': {
            const content = innerMd().trim();
            return content.split('\n').map(l => `> ${l}`).join('\n') + '\n\n';
        }

        case 'ul': {
            const items = Array.from(node.childNodes)
                .filter(n => n.nodeType === Node.ELEMENT_NODE && n.tagName.toLowerCase() === 'li')
                .map(li => {
                    const text = domToMarkdown(li, ctx).trim();
                    return `- ${text}`;
                }).join('\n');
            return items ? items + '\n\n' : '';
        }
        case 'ol': {
            const items = Array.from(node.childNodes)
                .filter(n => n.nodeType === Node.ELEMENT_NODE && n.tagName.toLowerCase() === 'li')
                .map((li, idx) => {
                    const text = domToMarkdown(li, ctx).trim();
                    return `${idx + 1}. ${text}`;
                }).join('\n');
            return items ? items + '\n\n' : '';
        }
        case 'li': return innerMd();

        case 'br': {
            // ブロック要素の末尾brは無視、それ以外は改行
            return '\n';
        }
        case 'hr': return '\n---\n\n';

        case 'p': {
            const content = innerMd().trim();
            if (!content) return '';
            return content + '\n\n';
        }
        case 'div': {
            const content = innerMd().trim();
            if (!content) return '';
            return content + '\n\n';
        }

        default:
            return innerMd();
    }
}

function switchEditorMode(mode) {
    if (mode === currentEditorMode) return;

    const mdWrap = document.getElementById('markdownEditorWrap');
    const rtWrap = document.getElementById('richtextEditorWrap');
    const mdTextarea = document.getElementById('markdownTextarea');
    const rtArea = document.getElementById('richtextArea');
    const tabs = document.querySelectorAll('.editor-mode-tab');
    const notice = document.getElementById('editorModeNotice');

    if (mode === 'richtext') {
        rtArea.innerHTML = markdownToHtml(mdTextarea.value);
        mdWrap.style.display = 'none';
        rtWrap.style.display = '';
        notice.textContent = '※ リッチテキストで編集中。保存時にMarkdownへ変換されます。';
    } else {
        mdTextarea.value = htmlToMarkdown(rtArea.innerHTML);
        rtWrap.style.display = 'none';
        mdWrap.style.display = '';
        notice.textContent = '';
    }

    tabs.forEach(t => t.classList.toggle('active', t.dataset.mode === mode));
    currentEditorMode = mode;
}

// ============================================================
// Markdownエディタ ツールバー操作
// ============================================================
function mdInsert(action) {
    const ta = document.getElementById('markdownTextarea');
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const selected = ta.value.substring(start, end);
    let before = '', after = '', defaultText = '';
    let cursorOffset = null;

    switch (action) {
        case 'heading1':    before = '# ';    defaultText = '見出し1'; break;
        case 'heading2':    before = '## ';   defaultText = '見出し2'; break;
        case 'heading3':    before = '### ';  defaultText = '見出し3'; break;
        case 'bold':        before = '**'; after = '**'; defaultText = '太字テキスト'; break;
        case 'italic':      before = '*';  after = '*';  defaultText = '斜体テキスト'; break;
        case 'strike':      before = '~~'; after = '~~'; defaultText = '取り消し線'; break;
        case 'link': {
            const url = prompt('URLを入力してください:', 'https://');
            if (!url) return;
            const text = selected || 'リンクテキスト';
            const insertion = `[${text}](${url})`;
            ta.setRangeText(insertion, start, end, 'end');
            ta.focus();
            return;
        }
        case 'image': {
            const url = prompt('画像URLを入力してください:', 'https://');
            if (!url) return;
            const alt = selected || '画像';
            const insertion = `![${alt}](${url})`;
            ta.setRangeText(insertion, start, end, 'end');
            ta.focus();
            return;
        }
        case 'ul': {
            const lines = (selected || 'リスト項目').split('\n').map(l => `- ${l}`).join('\n');
            ta.setRangeText('\n' + lines + '\n', start, end, 'end');
            ta.focus();
            return;
        }
        case 'ol': {
            const lines = (selected || 'リスト項目').split('\n').map((l, i) => `${i+1}. ${l}`).join('\n');
            ta.setRangeText('\n' + lines + '\n', start, end, 'end');
            ta.focus();
            return;
        }
        case 'blockquote': {
            const lines = (selected || '引用テキスト').split('\n').map(l => `> ${l}`).join('\n');
            ta.setRangeText('\n' + lines + '\n', start, end, 'end');
            ta.focus();
            return;
        }
        case 'code':        before = '`';  after = '`';  defaultText = 'code'; break;
        case 'codeblock':   before = '```\n'; after = '\n```'; defaultText = 'コードをここに'; break;
        case 'hr': {
            ta.setRangeText('\n\n---\n\n', start, end, 'end');
            ta.focus();
            return;
        }
    }

    const text = selected || defaultText;
    const insertion = before + text + after;
    ta.setRangeText(insertion, start, end, 'select');

    // 選択状態を挿入テキストのみに
    if (!selected) {
        ta.setSelectionRange(start + before.length, start + before.length + text.length);
    }
    ta.focus();
}

// キーボードショートカット（Markdownモード）
document.getElementById('markdownTextarea').addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey)) {
        if (e.key === 'b') { e.preventDefault(); mdInsert('bold'); }
        if (e.key === 'i') { e.preventDefault(); mdInsert('italic'); }
        if (e.key === 'k') { e.preventDefault(); mdInsert('link'); }
    }
});

// ============================================================
// リッチテキストエディタ操作
// ============================================================
function rtExec(cmd, value) {
    document.getElementById('richtextArea').focus();
    document.execCommand(cmd, false, value || null);
}

function rtExecBlock(tag) {
    if (!tag) return;
    document.getElementById('richtextArea').focus();
    document.execCommand('formatBlock', false, tag);
}

function rtInsertLink() {
    const selection = window.getSelection();
    const selectedText = selection ? selection.toString() : '';
    const url = prompt('リンクURLを入力してください:', 'https://');
    if (!url) return;
    document.getElementById('richtextArea').focus();
    if (selectedText) {
        document.execCommand('createLink', false, url);
    } else {
        const text = prompt('リンクテキストを入力してください:', 'リンク') || 'リンク';
        document.execCommand('insertHTML', false, `<a href="${url}">${text}</a>`);
    }
}

function rtInsertImage() {
    // savedSelectionを保持してからモーダルを開く
    const sel = window.getSelection();
    let savedRange = null;
    if (sel && sel.rangeCount > 0) {
        savedRange = sel.getRangeAt(0).cloneRange();
    }
    openMediaModal(function(url) {
        const area = document.getElementById('richtextArea');
        area.focus();
        if (savedRange) {
            const sel2 = window.getSelection();
            sel2.removeAllRanges();
            sel2.addRange(savedRange);
        }
        document.execCommand('insertImage', false, url);
    });
}

// キーボードショートカット（リッチテキストモード）
document.getElementById('richtextArea').addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey)) {
        if (e.key === 'k') {
            e.preventDefault();
            rtInsertLink();
        }
    }
});

// ============================================================
// フォーム送信（モード問わず content を同期）
// ============================================================
document.getElementById('editorForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // リッチテキストモードの場合、送信前に HTML→MD 変換して textarea に反映
    if (currentEditorMode === 'richtext') {
        const rtArea = document.getElementById('richtextArea');
        const mdTextarea = document.getElementById('markdownTextarea');
        mdTextarea.value = htmlToMarkdown(rtArea.innerHTML);
    }

    const formData = new FormData(this);
    fetch('api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) { 
                alert('保存しました'); 
                window.location.href = 'edit-posts.php'; 
            }
            else { alert('エラー: ' + (data.error || '不明なエラー')); }
        });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>