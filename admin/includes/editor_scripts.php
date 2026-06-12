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