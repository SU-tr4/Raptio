<?php
// admin/editor.php
require_once __DIR__ . '/config.php';
session_start();

if (!isset($_SESSION['raptio_auth'])) {
    header('Location: index.php');
    exit;
}

// モード判定: post（投稿）/ page（単独ページ）/ {cpt_slug}（カスタム投稿タイプ）
$mode = $_GET['mode'] ?? 'post';
$cpt_type = $_GET['type'] ?? '';   // CPTスラッグ（例: "news"）

// typeパラメータがあればCPTモード
if ($cpt_type !== '' && $cpt_type !== 'post' && $cpt_type !== 'page') {
    $mode = 'cpt';
} elseif ($mode !== 'page') {
    $mode = 'post';
}

// CPT設定の読み込み
$cpt_label = '';
if ($mode === 'cpt') {
    $cpt_config = defined('CPT_CONFIG_FILE') && file_exists(CPT_CONFIG_FILE)
        ? json_decode(file_get_contents(CPT_CONFIG_FILE), true) : [];
    $cpt_label  = $cpt_config[$cpt_type]['label'] ?? $cpt_type;
}

// カテゴリ（投稿モードのみ使用）
$all_categories = [];
if ($mode === 'post') {
    $categories_file = DATA_DIR . '/categories.json';
    $all_categories  = file_exists($categories_file) ? json_decode(file_get_contents($categories_file), true) : [];
    if (!is_array($all_categories)) $all_categories = [];
}

$id          = $_GET['id'] ?? '';
$title       = '';
$slug        = '';
$content     = '';
$status      = 'draft';
$date        = '';
$category_id = '';
$thumbnail   = '';

if ($id) {
    if ($mode === 'post') {
        $index_data = json_decode(file_exists(INDEX_FILE) ? file_get_contents(INDEX_FILE) : '[]', true) ?? [];
        foreach ($index_data as $post) {
            if ($post['id'] === $id) {
                $title       = $post['title'];
                $slug        = $post['slug'];
                $status      = $post['status'];
                $date        = $post['date'];
                $category_id = $post['category_id'] ?? '';
                $thumbnail   = $post['thumbnail'] ?? '';
                $full_path   = POSTS_DIR . '/' . basename($post['file_path']);
                if (!file_exists($full_path)) $full_path = __DIR__ . '/../' . $post['file_path'];
                if (file_exists($full_path)) $content = file_get_contents($full_path);
                break;
            }
        }
    } elseif ($mode === 'cpt') {
        $cpt_index_file = DATA_DIR . "/posts_{$cpt_type}_index.json";
        $index_data = json_decode(file_exists($cpt_index_file) ? file_get_contents($cpt_index_file) : '[]', true) ?? [];
        foreach ($index_data as $post) {
            if ($post['id'] === $id) {
                $title     = $post['title'];
                $slug      = $post['slug'];
                $status    = $post['status'];
                $date      = $post['date'];
                $thumbnail = $post['thumbnail'] ?? '';
                $fp        = $post['file_path'];
                if (!file_exists($fp)) $fp = __DIR__ . '/../' . $fp;
                if (file_exists($fp)) $content = file_get_contents($fp);
                break;
            }
        }
    } else {
        $pages_index = json_decode(file_exists(PAGES_INDEX_FILE) ? file_get_contents(PAGES_INDEX_FILE) : '[]', true) ?? [];
        foreach ($pages_index as $pg) {
            if ($pg['id'] === $id) {
                $title     = $pg['title'];
                $slug      = $pg['slug'];
                $status    = $pg['status'];
                $date      = $pg['date'];
                $thumbnail = $pg['thumbnail'] ?? '';
                $fp        = $pg['file_path'];
                if (!file_exists($fp)) $fp = __DIR__ . '/../' . $pg['file_path'];
                if (file_exists($fp)) $content = file_get_contents($fp);
                break;
            }
        }
    }
}

// ページタイトル・サイドバー変数
if ($mode === 'cpt') {
    $page_title   = $id ? $cpt_label . 'の編集' : '新規' . $cpt_label . 'を追加';
    $current_page = 'cpt_' . $cpt_type;
    $sub_page     = 'add';
    $save_action  = 'save_post';
    $list_page    = 'edit-posts.php?type=' . urlencode($cpt_type);
    $h2_label     = $page_title;
} elseif ($mode === 'post') {
    $page_title   = $id ? '投稿の編集' : '新規投稿を追加';
    $current_page = 'posts';
    $sub_page     = 'add';
    $save_action  = 'save_post';
    $list_page    = 'edit-posts.php';
    $h2_label     = $id ? '投稿の編集' : '新規投稿を追加';
} else {
    $page_title   = $id ? '単独ページの編集' : '新規単独ページを追加';
    $current_page = 'pages';
    $sub_page     = 'add';
    $save_action  = 'save_page';
    $list_page    = 'edit-pages.php';
    $h2_label     = $id ? '単独ページの編集' : '新規単独ページを追加';
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="wp-admin-header">
    <h2><?php echo htmlspecialchars($h2_label, ENT_QUOTES, 'UTF-8'); ?></h2>
</div>

<form id="editorForm">
    <input type="hidden" name="action" value="<?php echo $save_action; ?>">
    <input type="hidden" name="id"     value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($mode === 'cpt'): ?>
        <input type="hidden" name="type" value="<?php echo htmlspecialchars($cpt_type, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($date): ?>
        <input type="hidden" name="date" value="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <div class="editor-layout">
        <div class="editor-left">
            <div class="form-group-title">
                <input type="text" name="title" value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" placeholder="タイトルを追加" class="editor-title-input">
            </div>

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
                <textarea id="markdownTextarea" name="content" placeholder="ここに文章を入力してください（マークダウン対応）" class="editor-textarea"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

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
                        <button type="button" class="toolbar-btn" title="左揃え"   onclick="rtExec('justifyLeft')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg></button>
                        <button type="button" class="toolbar-btn" title="中央揃え" onclick="rtExec('justifyCenter')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg></button>
                        <button type="button" class="toolbar-btn" title="右揃え"   onclick="rtExec('justifyRight')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg></button>
                    </div>
                    <div class="toolbar-sep"></div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" title="箇条書き"    onclick="rtExec('insertUnorderedList')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg></button>
                        <button type="button" class="toolbar-btn" title="番号リスト"  onclick="rtExec('insertOrderedList')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg></button>
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
            <div id="library-grid" class="library-grid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(90px,1fr)); gap:8px; padding:12px; max-height:360px; overflow-y:auto;">
                <div class="library-loading" id="library-loading" style="display:none; grid-column:1/-1; text-align:center; padding:40px; color:#646970;">読み込み中...</div>
                <div class="library-empty"  id="library-empty"   style="display:none; grid-column:1/-1; text-align:center; padding:40px; color:#646970;">まだ画像がアップロードされていません。</div>
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

// パス補完ユーティリティ（APIから返ってくるURLをクリーンアップして管理画面用/保存用に仕分ける）
function resolveImageUrl(url) {
    if (!url) return '';
    // もしすでに管理画面用の相対表記になっている場合は、重複しないように一旦クリーンアップ
    let cleanPath = url;
    if (cleanPath.indexOf('../') === 0) {
        cleanPath = cleanPath.substring(3);
    }
    // ドキュメントルートからの絶対パスなどの場合は調整してください
    return cleanPath;
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
    const cleanUrl = isDataUrl ? url : resolveImageUrl(url);
    document.getElementById('thumbnail-input').value = isDataUrl ? '' : cleanUrl;
    
    const displayUrl = isDataUrl ? url : '../' + cleanUrl;
    const preview = document.getElementById('thumb-preview');
    preview.classList.add('has-image');
    preview.innerHTML = `<img src="${displayUrl}" alt="アイキャッチ画像"><button type="button" class="thumb-remove-btn" onclick="removeThumbnail()" title="削除">&times;</button>`;
    const btn = document.querySelector('.thumb-select-btn');
    if (btn) btn.textContent = '画像を変更';
    const removeLink = document.getElementById('thumb-remove-link');
    if (removeLink) removeLink.style.display = '';
}

// ============================================================
// メディアモーダル
// ============================================================
let selectedLibraryItem = null;
let pendingUploadFile   = null;
let _rtMediaCallback    = null;
let _libraryLoaded      = false;

function openMediaModal(callback) {
    selectedLibraryItem = null;
    pendingUploadFile   = null;
    _rtMediaCallback    = callback || null;
    updateFooter();
    document.getElementById('media-modal-overlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    // ライブラリタブに切り替わっているときは即ロード、そうでなければ初回タブ切替時にロード
    const activeTab = document.querySelector('.media-tab.active');
    if (activeTab && activeTab.dataset.tab === 'library') {
        loadLibrary();
    }
}

function closeMediaModal() {
    document.getElementById('media-modal-overlay').classList.remove('active');
    document.body.style.overflow = '';
    selectedLibraryItem = null;
    pendingUploadFile   = null;
    _rtMediaCallback    = null;
}

function closeMediaModalOnOverlay(e) {
    if (e.target === document.getElementById('media-modal-overlay')) closeMediaModal();
}

function switchTab(tab) {
    document.querySelectorAll('.media-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.media-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelector(`.media-tab[data-tab="${tab}"]`).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
    selectedLibraryItem = null;
    pendingUploadFile   = null;
    updateFooter();
    if (tab === 'library') loadLibrary();
}

// ライブラリ遅延ロード（モーダルを開いたときに初めて取得）
function loadLibrary() {
    if (_libraryLoaded) return;
    const loading = document.getElementById('library-loading');
    const empty   = document.getElementById('library-empty');
    const grid    = document.getElementById('library-grid');
    loading.style.display = '';
    empty.style.display   = 'none';

    fetch('api.php?action=get_media')
        .then(r => r.json())
        .then(files => {
            loading.style.display = 'none';
            // 既存アイテム（loadingとempty以外）を削除
            grid.querySelectorAll('.library-item').forEach(el => el.remove());
            if (!Array.isArray(files) || files.length === 0) {
                empty.style.display = '';
            } else {
                files.forEach(item => {
                    const cleanUrl = resolveImageUrl(item.url);
                    const el = document.createElement('div');
                    el.className    = 'library-item';
                    el.dataset.url  = cleanUrl;
                    el.dataset.name = item.name;
                    el.dataset.size = item.size || '';
                    el.onclick = function() { selectLibraryItem(this); };
                    el.innerHTML = `
                        <div class="library-item-img-wrap" style="position:relative; width:80px; height:80px; margin:0 auto;">
                            <img src="../${cleanUrl}" alt="${escHtml(item.name)}" loading="lazy" style="width:80px; height:80px; object-fit:cover; border-radius:3px; border:2px solid transparent; display:block;">
                            <div class="library-item-check" style="display:none; position:absolute; top:2px; right:2px; background:#2271b1; border-radius:50%; width:18px; height:18px; align-items:center; justify-content:center;"><svg viewBox="0 0 12 10" width="10" height="10"><polyline points="1,5 4.5,9 11,1" stroke="#fff" stroke-width="2" fill="none"/></svg></div>
                        </div>
                        <p class="library-item-name" style="font-size:10px; text-align:center; margin:4px 0 0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:80px;">${escHtml(item.name)}</p>`;
                    grid.appendChild(el);
                });
            }
            _libraryLoaded = true;
        })
        .catch(() => {
            loading.style.display = 'none';
            empty.style.display   = '';
            empty.textContent     = '読み込みに失敗しました。';
        });
}

// ライブラリにアイテムを追加（アップロード後）
function addToLibraryGrid(url, name, size) {
    _libraryLoaded = false; // 次回再取得させる（または直接追加）
    const grid    = document.getElementById('library-grid');
    const emptyEl = document.getElementById('library-empty');
    if (emptyEl) emptyEl.style.display = 'none';

    const cleanUrl = resolveImageUrl(url);
    const el = document.createElement('div');
    el.className    = 'library-item';
    el.dataset.url  = cleanUrl;
    el.dataset.name = name;
    el.dataset.size = size || '';
    el.onclick = function() { selectLibraryItem(this); };
    el.innerHTML = `
        <div class="library-item-img-wrap" style="position:relative; width:80px; height:80px; margin:0 auto;">
            <img src="../${cleanUrl}" alt="${escHtml(name)}" loading="lazy" style="width:80px; height:80px; object-fit:cover; border-radius:3px; border:2px solid transparent; display:block;">
            <div class="library-item-check" style="display:none; position:absolute; top:2px; right:2px; background:#2271b1; border-radius:50%; width:18px; height:18px; align-items:center; justify-content:center;"><svg viewBox="0 0 12 10" width="10" height="10"><polyline points="1,5 4.5,9 11,1" stroke="#fff" stroke-width="2" fill="none"/></svg></div>
        </div>
        <p class="library-item-name" style="font-size:10px; text-align:center; margin:4px 0 0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:80px;">${escHtml(name)}</p>`;
    grid.prepend(el);
    _libraryLoaded = true;
}

// ライブラリ：アイテム選択
function selectLibraryItem(el) {
    document.querySelectorAll('#library-grid .library-item').forEach(i => {
        i.querySelector('img').style.border = '2px solid transparent';
        const chk = i.querySelector('.library-item-check');
        if (chk) chk.style.display = 'none';
    });
    el.querySelector('img').style.border = '2px solid #2271b1';
    const chk = el.querySelector('.library-item-check');
    if (chk) chk.style.display = 'flex';
    selectedLibraryItem = { url: el.dataset.url, name: el.dataset.name, size: el.dataset.size };
    updateFooter();
}

// ライブラリ検索フィルター
function filterLibrary(q) {
    const items = document.querySelectorAll('#library-grid .library-item');
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
    inner.style.display     = 'none';
    uploading.style.display = 'flex';

    const formData = new FormData();
    formData.append('action', 'upload_media');
    formData.append('file', file);

    try {
        const res  = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success && data.url) {
            const cleanUrl = resolveImageUrl(data.url);
            if (_rtMediaCallback) {
                _rtMediaCallback(cleanUrl);
            } else {
                applyThumbnail(cleanUrl, false);
            }
            addToLibraryGrid(cleanUrl, data.name || file.name, data.size || '');
        } else {
            const reader = new FileReader();
            reader.onload = e => {
                if (_rtMediaCallback) _rtMediaCallback(e.target.result);
                else applyThumbnail(e.target.result, true);
            };
            reader.readAsDataURL(file);
            if (!_rtMediaCallback) assignFileToHiddenInput(file);
        }
    } catch {
        const reader = new FileReader();
        reader.onload = e => {
            if (_rtMediaCallback) _rtMediaCallback(e.target.result);
            else applyThumbnail(e.target.result, true);
        };
        reader.readAsDataURL(file);
        if (!_rtMediaCallback) assignFileToHiddenInput(file);
    }

    inner.style.display     = '';
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

function markdownToHtml(md) {
    if (!md || !md.trim()) return '<p><br></p>';

    // コードブロックを退避
    const codeBlocks = [];
    md = md.replace(/```[\s\S]*?```/g, match => {
        codeBlocks.push(match);
        return `\x00CODE${codeBlocks.length - 1}\x00`;
    });

    let html = md
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/^#{6}\s(.+)$/gm,'<h6>$1</h6>')
        .replace(/^#{5}\s(.+)$/gm,'<h5>$1</h5>')
        .replace(/^#{4}\s(.+)$/gm,'<h4>$1</h4>')
        .replace(/^#{3}\s(.+)$/gm,'<h3>$1</h3>')
        .replace(/^#{2}\s(.+)$/gm,'<h2>$1</h2>')
        .replace(/^#\s(.+)$/gm,'<h1>$1</h1>')
        .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
        .replace(/\*(.+?)\*/g,'<em>$1</em>')
        .replace(/~~(.+?)~~/g,'<del>$1</del>')
        .replace(/`(.+?)`/g,'<code>$1</code>')
        .replace(/!\[([^\]]*)\]\(([^)]+)\)/g, (m, alt, src) => {
            // エディタ内プレビュー表示用。外部URLでなければ ../ を付与して表示させる
            const displaySrc = (src.indexOf('http') === 0 || src.indexOf('data:') === 0) ? src : '../' + resolveImageUrl(src);
            return `<img src="${displaySrc}" data-saved-src="${src}" alt="${alt}">`;
        })
        .replace(/\[([^\]]+)\]\(([^)]+)\)/g,'<a href="$2">$1</a>')
        .replace(/^---$/gm,'<hr>')
        .replace(/\n/g,'<br>');

    // コードブロックを復元
    codeBlocks.forEach((block, i) => {
        const code = block.replace(/^```[^\n]*\n?/, '').replace(/```$/, '');
        html = html.replace(`\x00CODE${i}\x00`, `<pre><code>${escHtml(code.trim())}</code></pre>`);
    });

    return html;
}

function htmlToMarkdown(html) {
    return html
        .replace(/<h1[^>]*>(.*?)<\/h1>/gi, '# $1\n')
        .replace(/<h2[^>]*>(.*?)<\/h2>/gi, '## $1\n')
        .replace(/<h3[^>]*>(.*?)<\/h3>/gi, '### $1\n')
        .replace(/<h4[^>]*>(.*?)<\/h4>/gi, '#### $1\n')
        .replace(/<pre[^>]*><code[^>]*>([\s\S]*?)<\/code><\/pre>/gi, (m, c) => '```\n' + c.replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>') + '\n```\n')
        .replace(/<strong[^>]*>(.*?)<\/strong>/gi, '**$1**')
        .replace(/<b[^>]*>(.*?)<\/b>/gi, '**$1**')
        .replace(/<em[^>]*>(.*?)<\/em>/gi, '*$1*')
        .replace(/<i[^>]*>(.*?)<\/i>/gi, '*$1*')
        .replace(/<del[^>]*>(.*?)<\/del>/gi, '~~$1~~')
        .replace(/<code[^>]*>(.*?)<\/code>/gi, '`$1`')
        .replace(/<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/gi, '[$2]($1)')
        .replace(/<img[^>]+src="([^"]+)"[^>]*>/gi, (match) => {
            // リッチテキストからマークダウンに戻す際、data-saved-src属性があればそちら（純粋なuploads/パス）を優先
            const savedSrcMatch = match.match(/data-saved-src="([^"]+)"/i);
            const srcMatch = match.match(/src="([^"]+)"/i);
            const altMatch = match.match(/alt="([^"]*)"/i);
            
            let src = savedSrcMatch ? savedSrcMatch[1] : (srcMatch ? srcMatch[1] : '');
            let alt = altMatch ? altMatch[1] : '';
            
            // もしsrcに管理画面用相対パスが入っていたら除去してクリーンにする
            if (src.indexOf('../') === 0 && src.indexOf('data:') !== 0) {
                src = src.substring(3);
            }
            return `![${alt}](${src})`;
        })
        .replace(/<hr[^>]*>/gi, '\n---\n')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/?p[^>]*>/gi, '\n')
        .replace(/<[^>]+>/g, '')
        .replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&nbsp;/g,' ')
        .replace(/\n{3,}/g, '\n\n').trim();
}

function switchEditorMode(mode) {
    const mdWrap = document.getElementById('markdownEditorWrap');
    const rtWrap = document.getElementById('richtextEditorWrap');
    const notice = document.getElementById('editorModeNotice');

    if (mode === currentEditorMode) return;

    if (mode === 'richtext') {
        const md = document.getElementById('markdownTextarea').value;
        document.getElementById('richtextArea').innerHTML = markdownToHtml(md);
        mdWrap.style.display = 'none';
        rtWrap.style.display = '';
        notice.textContent = 'リッチテキストモード（保存時にMarkdownへ変換されます）';
    } else {
        const html = document.getElementById('richtextArea').innerHTML;
        document.getElementById('markdownTextarea').value = htmlToMarkdown(html);
        rtWrap.style.display = 'none';
        mdWrap.style.display = '';
        notice.textContent = '';
    }

    document.querySelectorAll('.editor-mode-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.mode === mode);
    });
    currentEditorMode = mode;
}

// ============================================================
// Markdownツールバー操作
// ============================================================
function mdInsert(type) {
    const ta    = document.getElementById('markdownTextarea');
    const start = ta.selectionStart;
    const end   = ta.selectionEnd;
    const selected = ta.value.substring(start, end);
    let before = '', after = '', defaultText = '';

    switch (type) {
        case 'heading1':    before = '# ';   after = '';   defaultText = '見出し1'; break;
        case 'heading2':    before = '## ';  after = '';   defaultText = '見出し2'; break;
        case 'heading3':    before = '### '; after = '';   defaultText = '見出し3'; break;
        case 'bold':        before = '**';   after = '**'; defaultText = '太字'; break;
        case 'italic':      before = '*';    after = '*';  defaultText = '斜体'; break;
        case 'strike':      before = '~~';   after = '~~'; defaultText = '取り消し線'; break;
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
            // 手動入力からメディアモーダル連動へアップグレード
            openMediaModal(function(url) {
                const cleanUrl = resolveImageUrl(url);
                const alt = selected || '画像';
                const insertion = `![${alt}](${cleanUrl})`;
                ta.setRangeText(insertion, start, end, 'end');
                ta.focus();
            });
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
        case 'code':        before = '`';    after = '`';      defaultText = 'code'; break;
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
        
        const cleanUrl = resolveImageUrl(url);
        const displaySrc = (cleanUrl.indexOf('http') === 0 || cleanUrl.indexOf('data:') === 0) ? cleanUrl : '../' + cleanUrl;
        
        // 純粋なエディタコマンドでの挿入ではなく、属性をコントロールできるHTML挿入にすることでリンク切れを防ぎます
        const imgHtml = `<img src="${displaySrc}" data-saved-src="${cleanUrl}" alt="画像">`;
        document.execCommand('insertHTML', false, imgHtml);
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
// スラッグ自動生成（新規投稿のみ・タイトル入力時）
// ============================================================
(function() {
    const isNew = <?php echo $id ? 'false' : 'true'; ?>;
    if (!isNew) return; // 編集時は自動生成しない

    const slugInput = document.getElementById('slug');
    let slugManuallyEdited = false;

    // スラッグ欄を手動編集したらフラグを立てる
    slugInput.addEventListener('input', function() {
        slugManuallyEdited = this.value.trim() !== '';
    });

    // 今日の日付を YYYYMMDD 形式で返す
    function getTodayPrefix() {
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return y + m + day;
    }

    // タイトル入力時: スラッグが空か日付デフォルトのままなら更新
    document.querySelector('input[name="title"]').addEventListener('input', function() {
        if (slugManuallyEdited) return;
        if (!slugInput.value.trim()) {
            slugInput.value = getTodayPrefix();
        }
    });

    // ページ初期表示時にスラッグが空なら日付をセット
    if (!slugInput.value.trim()) {
        slugInput.value = getTodayPrefix();
        // 日付デフォルト値は「手動編集」とみなさない
        slugManuallyEdited = false;
    }
})();

// ============================================================
// フォーム送信
// ============================================================
document.getElementById('editorForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // JSバリデーション
    const title = document.querySelector('input[name="title"]').value.trim();
    const slug  = document.getElementById('slug').value.trim();
    if (!title) { alert('タイトルを入力してください。'); return; }
    if (!slug)  { alert('スラッグを入力してください。'); return; }

    // リッチテキストモードのとき textarea に反映
    if (currentEditorMode === 'richtext') {
        const rtArea     = document.getElementById('richtextArea');
        const mdTextarea = document.getElementById('markdownTextarea');
        mdTextarea.value = htmlToMarkdown(rtArea.innerHTML);
    }

    const btn = document.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '保存中...';

    const formData = new FormData(this);
    fetch('api.php', { method: 'POST', body: formData })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            if (data.success) {
                alert('保存しました');
                window.location.href = '<?php echo $list_page; ?>';
            } else {
                alert('エラー: ' + (data.message || data.error || '不明なエラー'));
                btn.disabled = false;
                btn.textContent = '公開・保存';
            }
        })
        .catch(err => {
            alert('通信エラーが発生しました。\n' + err.message);
            btn.disabled = false;
            btn.textContent = '公開・保存';
        });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>