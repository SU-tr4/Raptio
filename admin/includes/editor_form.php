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

        <?php require_once __DIR__ . '/editor_sidebar.php'; ?>
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