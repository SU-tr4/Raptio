<?php
// admin/site-menu.php
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }

$config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
$menus       = $config_data['menus'] ?? [];
$locations   = $config_data['menu_locations'] ?? [];

// メニュー項目追加パネル用データ取得
$categories  = file_exists(CATEGORY_FILE)    ? json_decode(file_get_contents(CATEGORY_FILE),    true) : [];
$cpt_config  = file_exists(CPT_CONFIG_FILE)  ? json_decode(file_get_contents(CPT_CONFIG_FILE),   true) : [];

// 投稿・単独ページ・カスタム投稿のデータをインデックスJSONから収集
$data_dir      = defined('DATA_DIR') ? DATA_DIR : realpath(__DIR__ . '/../data');
$index_file    = defined('INDEX_FILE')       ? INDEX_FILE       : $data_dir . '/posts_index.json';
$pages_index   = defined('PAGES_INDEX_FILE') ? PAGES_INDEX_FILE : $data_dir . '/pages_index.json';

// 通常投稿 (posts_index.json)
$posts_raw     = file_exists($index_file)  ? json_decode(file_get_contents($index_file),  true) : [];
$published_posts = array_filter(
    is_array($posts_raw) ? $posts_raw : [],
    fn($p) => ($p['status'] ?? '') === 'public'
);

// 単独ページ (pages_index.json)
$pages_raw     = file_exists($pages_index) ? json_decode(file_get_contents($pages_index), true) : [];
$published_pages = array_filter(
    is_array($pages_raw) ? $pages_raw : [],
    fn($p) => ($p['status'] ?? '') === 'public'
);

// カスタム投稿タイプ: cpt_config.json のキー一覧から posts_{type}_index.json を読む
$cpt_posts_all = [];
if (is_array($cpt_config)) {
    foreach (array_keys($cpt_config) as $cpt_type) {
        $cpt_index = $data_dir . "/posts_{$cpt_type}_index.json";
        if (!file_exists($cpt_index)) continue;
        $cpt_raw = json_decode(file_get_contents($cpt_index), true);
        if (!is_array($cpt_raw)) continue;
        foreach ($cpt_raw as $item) {
            if (($item['status'] ?? '') !== 'public') continue;
            $cpt_label = $cpt_config[$cpt_type]['label'] ?? $cpt_type;
            $cpt_posts_all[] = array_merge($item, ['_cpt_label' => $cpt_label, 'type' => $cpt_type]);
        }
    }
}

$positions = [
    'header'            => 'ヘッダーメニュー',
    'header_mobile'     => 'ヘッダーモバイルメニュー',
    'header_mobile_btn' => 'ヘッダーモバイルボタン',
    'footer'            => 'フッターメニュー',
    'footer_mobile_btn' => 'フッターモバイルボタン',
    'mobile_slide'      => 'モバイルスライドインメニュー',
];

$current_gid   = $_GET['gid'] ?? (key($menus) ?: '');
$current_group = $menus[$current_gid] ?? ['name' => '新規メニュー', 'items' => []];
$current_page  = 'menu';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<style>
/* ================================================================
   site-menu.php 専用スタイル
   admin_style.css の競合定義を上書き・補完する
================================================================ */

/* ── レイアウト上書き（admin_style.css は flex/width:250px） ── */
.menu-manager-layout {
    display: grid !important;
    grid-template-columns: 280px 1fr;
    gap: 20px;
    align-items: start;
    margin-top: 20px;
}

/* 左パネル：admin_style.css の width/padding/border をリセットして積み直す */
.menu-list-panel {
    width: auto !important;
    background: transparent !important;
    border: none !important;
    padding: 0 !important;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* 右パネル：admin_style.css の padding をリセット */
.menu-edit-panel {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px !important;
    border-radius: 2px;
}

/* ── 追加ボックス ── */
.menu-add-box {
    border: 1px solid #c3c4c7;
    background: #fff;
    border-radius: 2px;
    overflow: hidden;
}
.menu-add-box-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f6f7f7;
    border-bottom: 1px solid #c3c4c7;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
}
.menu-add-box-title .toggle-arrow { font-size: 10px; transition: transform .2s; }
.menu-add-box-title.collapsed .toggle-arrow { transform: rotate(-90deg); }
.menu-add-box-body { padding: 10px 12px 12px; }
.menu-add-box-body.hidden { display: none; }

/* ── タブ ── */
.add-tab-bar {
    display: flex;
    border-bottom: 1px solid #c3c4c7;
    margin-bottom: 8px;
}
.add-tab {
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: none;
    color: #50575e;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    font-family: inherit;
}
.add-tab.active { color: #2271b1; border-bottom-color: #2271b1; }
.add-tab-pane { display: none; }
.add-tab-pane.active { display: block; }

/* ── チェックリスト ── */
.add-checklist {
    max-height: 160px;
    overflow-y: auto;
    border: 1px solid #dcdcde;
    border-radius: 2px;
    margin-bottom: 8px;
}
/* admin_style.css の label はデフォルトなし。display:flex を確実に指定 */
.add-checklist label {
    display: flex !important;
    align-items: center;
    gap: 7px;
    padding: 5px 8px;
    font-size: 12px;
    font-weight: normal;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f1;
    margin-bottom: 0;
}
.add-checklist label:last-child { border-bottom: none; }
.add-checklist label:hover { background: #f0f0f0; }
.add-checklist-empty { padding: 8px; font-size: 12px; color: #888; }

/* ── カスタムリンク ── */
.custom-url-field { display: flex; flex-direction: column; gap: 6px; }
.custom-url-field label {
    display: block !important;
    font-size: 12px;
    font-weight: 600;
    color: #3c434a;
    margin-bottom: 0;
}
/* admin_style.css の input[type="text"] { width:100%; padding:8px 12px } を縮小 */
.custom-url-field input[type="text"] {
    padding: 5px 7px !important;
    font-size: 12px !important;
    width: 100% !important;
}

/* ── メニューに追加ボタン ── */
.btn-add-to-menu {
    width: 100%;
    padding: 6px 0;
    font-size: 12px;
    font-weight: 600;
    background: #2271b1;
    color: #fff;
    border: none;
    border-radius: 2px;
    cursor: pointer;
    margin-top: 6px;
    font-family: inherit;
}
.btn-add-to-menu:hover { background: #135e96; }

/* ── メニュー項目リスト ── */
#items-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 16px;
}

/* admin_style.css の .menu-item-row を完全上書き */
.menu-item-row {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 7px 10px !important;
    margin-bottom: 0 !important;
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 3px;
    cursor: default;
    transition: box-shadow .15s, border-color .15s;
    user-select: none;
}
/* admin_style.css の input { flex:1; padding:6px 8px } を全て打ち消す */
.menu-item-row input {
    flex: unset !important;
    padding: 0 !important;
    width: auto !important;
    border: none !important;
    box-shadow: none !important;
    background: none !important;
}
.menu-item-row input[type="checkbox"] {
    width: 15px !important;
    height: 15px !important;
    flex-shrink: 0 !important;
    cursor: pointer;
    accent-color: #2271b1;
    margin: 0 !important;
}
.menu-item-row.dragging  { opacity: .4; }
.menu-item-row.drag-over { border-color: #2271b1; box-shadow: 0 0 0 2px #c7e0f4; }

/* ドラッグハンドル */
.drag-handle {
    display: flex;
    flex-direction: column;
    gap: 3px;
    cursor: grab;
    padding: 2px 4px;
    flex-shrink: 0;
    color: #b4b9be;
}
.drag-handle:active { cursor: grabbing; }
.drag-handle span {
    display: block;
    width: 16px;
    height: 2px;
    background: currentColor;
    border-radius: 1px;
}

/* 選択チェックボックス（上の .menu-item-row input で基本リセット済み） */
.item-select-chk {
    flex-shrink: 0;
    width: 15px !important;
    height: 15px !important;
    cursor: pointer;
    accent-color: #2271b1;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    box-shadow: none !important;
}

/* hidden input がレイアウトに影響しないよう完全に潰す */
.menu-item-row input[type="hidden"] {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    flex: none !important;
}

/* ラベル・URL表示 */
.item-label-display {
    flex: 1;
    font-size: 13px;
    font-weight: 600;
    color: #1d2327;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}
.item-url-display {
    font-size: 11px;
    color: #8c8f94;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 180px;
    min-width: 0;
    flex-shrink: 1;
}

/* 操作ボタン */
.item-actions { display: flex; gap: 4px; flex-shrink: 0; }
.btn-item-edit {
    padding: 3px 10px;
    font-size: 12px;
    font-weight: 600;
    background: #f6f7f7;
    color: #2271b1;
    border: 1px solid #c3c4c7;
    border-radius: 2px;
    cursor: pointer;
    line-height: 1.5;
    font-family: inherit;
    white-space: nowrap;
}
.btn-item-edit:hover { background: #e8f0fe; border-color: #2271b1; }
.btn-item-del {
    padding: 3px 8px;
    font-size: 12px;
    font-weight: 600;
    background: #fff;
    color: #b32d2e;
    border: 1px solid #c3c4c7;
    border-radius: 2px;
    cursor: pointer;
    line-height: 1.5;
    font-family: inherit;
    white-space: nowrap;
}
.btn-item-del:hover { background: #fbeaea; border-color: #b32d2e; }

/* ── 一括操作バー ── */
.bulk-action-bar {
    display: none;
    align-items: center;
    gap: 10px;
    padding: 7px 10px;
    background: #f0f6fc;
    border: 1px solid #c7e0f4;
    border-radius: 3px;
    margin-bottom: 8px;
    font-size: 13px;
}
.bulk-action-bar.visible { display: flex; }
.bulk-action-bar .bulk-count { font-weight: 600; color: #2271b1; flex: 1; }
.btn-bulk-delete {
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 600;
    background: #b32d2e;
    color: #fff;
    border: none;
    border-radius: 2px;
    cursor: pointer;
    font-family: inherit;
}
.btn-bulk-delete:hover { background: #8a1f20; }
.btn-bulk-cancel {
    padding: 4px 10px;
    font-size: 12px;
    background: #fff;
    color: #50575e;
    border: 1px solid #c3c4c7;
    border-radius: 2px;
    cursor: pointer;
    font-family: inherit;
}

/* ── 編集モーダル ── */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: #fff;
    border-radius: 4px;
    width: 440px;
    max-width: calc(100vw - 40px);
    box-shadow: 0 8px 32px rgba(0,0,0,.28);
    overflow: hidden;
}
/* admin_style.css に .modal-header があるが padding が異なる → 上書き */
.modal-box .modal-header {
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    padding: 13px 18px !important;
    background: #f6f7f7;
    border-bottom: 1px solid #dcdcde;
    font-size: 16px;
    font-weight: 600;
}
.modal-box .modal-header h3 { margin: 0; font-size: 14px; font-weight: 700; }
.modal-close {
    background: none;
    border: none;
    font-size: 20px;
    line-height: 1;
    cursor: pointer;
    color: #787c82;
    padding: 2px 4px;
    font-family: inherit;
}
.modal-close:hover { color: #1d2327; }
.modal-body {
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.modal-field { display: flex; flex-direction: column; gap: 5px; }
.modal-field label {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #3c434a !important;
    display: block !important;
    margin-bottom: 0 !important;
}
.modal-field input[type="text"] {
    padding: 7px 9px !important;
    font-size: 13px !important;
    border: 1px solid #c3c4c7 !important;
    border-radius: 2px !important;
    width: 100% !important;
    box-shadow: none !important;
}
.modal-field input[type="text"]:focus {
    border-color: #2271b1 !important;
    box-shadow: 0 0 0 1px #2271b1 !important;
    outline: none !important;
}
/* admin_style.css の .modal-footer と区別するため .modal-box を親にする */
.modal-box .modal-footer {
    display: flex !important;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    padding: 12px 20px !important;
    border-top: 1px solid #dcdcde;
    background: #f6f7f7;
}
.btn-modal-save {
    padding: 6px 18px;
    font-size: 13px;
    font-weight: 600;
    background: #2271b1;
    color: #fff;
    border: none;
    border-radius: 2px;
    cursor: pointer;
    font-family: inherit;
}
.btn-modal-save:hover { background: #135e96; }
.btn-modal-cancel {
    padding: 6px 14px;
    font-size: 13px;
    background: #fff;
    color: #50575e;
    border: 1px solid #c3c4c7;
    border-radius: 2px;
    cursor: pointer;
    font-family: inherit;
}

/* ── 確認ダイアログ ── */
.confirm-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 100000;
    align-items: center;
    justify-content: center;
}
.confirm-overlay.open { display: flex; }
.confirm-box {
    background: #fff;
    border-radius: 4px;
    width: 360px;
    max-width: calc(100vw - 40px);
    box-shadow: 0 8px 32px rgba(0,0,0,.28);
    overflow: hidden;
}
.confirm-box-header {
    padding: 14px 18px;
    background: #fbeaea;
    border-bottom: 1px solid #f5c2c2;
    font-size: 14px;
    font-weight: 700;
    color: #8a1f20;
}
.confirm-box-body { padding: 16px 20px; font-size: 13px; color: #3c434a; line-height: 1.6; }
.confirm-box-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 20px;
    border-top: 1px solid #dcdcde;
}
.btn-confirm-ok {
    padding: 6px 18px;
    font-size: 13px;
    font-weight: 600;
    background: #b32d2e;
    color: #fff;
    border: none;
    border-radius: 2px;
    cursor: pointer;
    font-family: inherit;
}
.btn-confirm-ok:hover { background: #8a1f20; }
.btn-confirm-cancel {
    padding: 6px 14px;
    font-size: 13px;
    background: #fff;
    color: #50575e;
    border: 1px solid #c3c4c7;
    border-radius: 2px;
    cursor: pointer;
    font-family: inherit;
}
</style>

<div class="wp-content-title-area"><h2>メニュー管理</h2></div>

<div class="menu-manager-layout">

    <div class="menu-list-panel">

        <div class="menu-add-box">
            <div class="menu-add-box-title collapsed" onclick="toggleBox(this)">
                メニューセット <span class="toggle-arrow">▼</span>
            </div>
            <div class="menu-add-box-body hidden">
                <ul style="list-style:none;padding:0;margin:0 0 8px;">
                    <?php foreach ($menus as $gid => $group): ?>
                    <li style="margin-bottom:4px;">
                        <a href="?gid=<?php echo htmlspecialchars($gid); ?>"
                           style="display:block;padding:7px 10px;border:1px solid #c3c4c7;font-size:13px;font-weight:600;text-decoration:none;
                                  background:<?php echo ($gid===$current_gid)?'#2271b1':'#f6f7f7'; ?>;
                                  color:<?php echo ($gid===$current_gid)?'#fff':'#3c434a'; ?>;">
                            <?php echo htmlspecialchars($group['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" onclick="createNewMenu()" class="button button-secondary" style="width:100%;font-size:12px;">+ 新規作成</button>
            </div>
        </div>

        <?php if ($current_gid): ?>

        <div class="menu-add-box">
            <div class="menu-add-box-title collapsed" onclick="toggleBox(this)">
                投稿 <span class="toggle-arrow">▼</span>
            </div>
            <div class="menu-add-box-body hidden">
                <div class="add-tab-bar">
                    <button type="button" class="add-tab active" onclick="switchTab(this,'tab-post-recent')">最近</button>
                    <button type="button" class="add-tab" onclick="switchTab(this,'tab-post-all')">すべて</button>
                </div>
                <div id="tab-post-recent" class="add-tab-pane active">
                    <div class="add-checklist" id="checklist-post-recent">
                        <?php
                        $recent = array_slice(array_reverse(array_values($published_posts)), 0, 10);
                        if ($recent): ?>
                        <?php foreach ($recent as $p): ?>
                        <label>
                            <input type="checkbox" class="add-check" data-label="<?php echo htmlspecialchars($p['title']); ?>" data-url="<?php echo htmlspecialchars('?slug='.$p['slug']); ?>">
                            <?php echo htmlspecialchars($p['title']); ?>
                        </label>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="add-checklist-empty">公開中の投稿がありません</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="tab-post-all" class="add-tab-pane">
                    <div class="add-checklist" id="checklist-post-all">
                        <?php if ($published_posts): ?>
                        <?php foreach ($published_posts as $p): ?>
                        <label>
                            <input type="checkbox" class="add-check" data-label="<?php echo htmlspecialchars($p['title']); ?>" data-url="<?php echo htmlspecialchars('?slug='.$p['slug']); ?>">
                            <?php echo htmlspecialchars($p['title']); ?>
                        </label>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="add-checklist-empty">公開中の投稿がありません</div>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="button" class="btn-add-to-menu" onclick="addCheckedItems('checklist-post-recent','checklist-post-all')">メニューに追加</button>
            </div>
        </div>

        <div class="menu-add-box">
            <div class="menu-add-box-title collapsed" onclick="toggleBox(this)">
                単独 <span class="toggle-arrow">▼</span>
            </div>
            <div class="menu-add-box-body hidden">
                <div class="add-checklist" id="checklist-pages">
                    <?php if ($published_pages): ?>
                    <?php foreach ($published_pages as $p): ?>
                    <label>
                        <input type="checkbox" class="add-check" data-label="<?php echo htmlspecialchars($p['title']); ?>" data-url="<?php echo htmlspecialchars('?slug='.$p['slug']); ?>">
                        <?php echo htmlspecialchars($p['title']); ?>
                    </label>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="add-checklist-empty">公開中の単独ページがありません</div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-add-to-menu" onclick="addCheckedItems('checklist-pages')">メニューに追加</button>
            </div>
        </div>

        <div class="menu-add-box">
            <div class="menu-add-box-title collapsed" onclick="toggleBox(this)">
                カテゴリー <span class="toggle-arrow">▼</span>
            </div>
            <div class="menu-add-box-body hidden">
                <div class="add-checklist" id="checklist-cats">
                    <?php if ($categories): ?>
                    <?php foreach ($categories as $cat): ?>
                    <label>
                        <input type="checkbox" class="add-check" data-label="<?php echo htmlspecialchars($cat['name']); ?>" data-url="<?php echo htmlspecialchars('?category='.$cat['slug']); ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </label>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="add-checklist-empty">カテゴリーがありません</div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-add-to-menu" onclick="addCheckedItems('checklist-cats')">メニューに追加</button>
            </div>
        </div>

        <div class="menu-add-box">
            <div class="menu-add-box-title collapsed" onclick="toggleBox(this)">
                カスタム投稿 <span class="toggle-arrow">▼</span>
            </div>
            <div class="menu-add-box-body hidden">
                <div class="add-checklist" id="checklist-cpt">
                    <?php if ($cpt_posts_all): ?>
                    <?php foreach ($cpt_posts_all as $p): ?>
                    <label>
                        <input type="checkbox" class="add-check"
                               data-label="<?php echo htmlspecialchars($p['title']); ?>"
                               data-url="<?php echo htmlspecialchars('?slug='.$p['slug']); ?>">
                        <span style="color:#888;font-size:11px;">[<?php echo htmlspecialchars($p['_cpt_label']); ?>]</span>
                        <?php echo htmlspecialchars($p['title']); ?>
                    </label>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="add-checklist-empty">公開中のカスタム投稿がありません</div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-add-to-menu" onclick="addCheckedItems('checklist-cpt')">メニューに追加</button>
            </div>
        </div>

        <div class="menu-add-box">
            <div class="menu-add-box-title collapsed" onclick="toggleBox(this)">
                カスタムリンク <span class="toggle-arrow">▼</span>
            </div>
            <div class="menu-add-box-body hidden">
                <div class="custom-url-field">
                    <label for="custom-url">URL</label>
                    <input type="text" id="custom-url" placeholder="https://example.com">
                    <label for="custom-label">リンク文字列</label>
                    <input type="text" id="custom-label" placeholder="ホーム">
                </div>
                <button type="button" class="btn-add-to-menu" onclick="addCustomLink()">メニューに追加</button>
            </div>
        </div>

        <?php endif; // current_gid ?>

    </div><div class="menu-edit-panel">
        <form id="menuForm">
            <input type="hidden" name="action" value="save_menus">
            <input type="hidden" name="active_gid" value="<?php echo htmlspecialchars($current_gid); ?>">

            <?php if ($current_gid): ?>
            <div style="margin-bottom:20px;">
                <label style="font-weight:bold;">メニュー名:</label>
                <input type="text" name="menu_groups[<?php echo htmlspecialchars($current_gid); ?>][name]"
                       value="<?php echo htmlspecialchars($current_group['name']); ?>"
                       style="width:100%;padding:8px;margin-top:5px;border:1px solid #c3c4c7;border-radius:2px;">
            </div>

            <div id="bulk-bar" class="bulk-action-bar">
                <span class="bulk-count" id="bulk-count-label">0件 選択中</span>
                <button type="button" class="btn-bulk-cancel" onclick="clearSelection()">キャンセル</button>
                <button type="button" class="btn-bulk-delete" onclick="bulkDeleteSelected()">選択した項目を削除</button>
            </div>

            <div id="items-list">
                <?php foreach (($current_group['items'] ?? []) as $idx => $item): ?>
                <div class="menu-item-row" draggable="true"
                     data-label="<?php echo htmlspecialchars($item['label'], ENT_QUOTES); ?>"
                     data-url="<?php echo htmlspecialchars($item['url'], ENT_QUOTES); ?>">
                    <input type="checkbox" class="item-select-chk" onchange="onSelectChange()">
                    <span class="drag-handle" title="ドラッグして並び替え">
                        <span></span><span></span><span></span>
                    </span>
                    <span class="item-label-display"><?php echo htmlspecialchars($item['label']); ?></span>
                    <span class="item-url-display"><?php echo htmlspecialchars($item['url']); ?></span>
                    <div class="item-actions">
                        <button type="button" class="btn-item-edit" onclick="openEditModal(this)">編集</button>
                        <button type="button" class="btn-item-del"  onclick="confirmDeleteOne(this)">削除</button>
                    </div>
                    <!-- hidden inputs for form submit -->
                    <input type="hidden" name="menu_groups[<?php echo htmlspecialchars($current_gid); ?>][items][<?php echo $idx; ?>][label]" value="<?php echo htmlspecialchars($item['label']); ?>">
                    <input type="hidden" name="menu_groups[<?php echo htmlspecialchars($current_gid); ?>][items][<?php echo $idx; ?>][url]"   value="<?php echo htmlspecialchars($item['url']); ?>">
                </div>
                <?php endforeach; ?>
            </div>

            <div style="border-top:1px solid #c3c4c7;padding-top:20px;margin-top:8px;">
                <h3 style="margin-bottom:12px;font-size:14px;font-weight:600;">メニューの位置</h3>
                <div class="menu-locations-grid">
                    <?php foreach ($positions as $pos_key => $pos_label): ?>
                    <label class="menu-location-label">
                        <input type="checkbox" name="menu_locations[<?php echo $pos_key; ?>]"
                               value="<?php echo htmlspecialchars($current_gid); ?>"
                               <?php echo (isset($locations[$pos_key]) && $locations[$pos_key] === $current_gid) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($pos_label); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="button button-primary" style="margin-top:20px;padding:8px 20px;">変更を保存</button>
            <?php else: ?>
            <p style="color:#888;font-size:13px;">左のパネルからメニューを選択するか、新規作成してください。</p>
            <?php endif; ?>
        </form>
    </div>

</div>

<!-- ── 編集モーダル ── -->
<div class="modal-overlay" id="editModal" onclick="if(event.target===this)closeEditModal()">
    <div class="modal-box">
        <div class="modal-header">
            <h3>メニュー項目を編集</h3>
            <button class="modal-close" onclick="closeEditModal()" title="閉じる">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-field">
                <label for="modal-label">ラベル</label>
                <input type="text" id="modal-label" placeholder="ラベル">
            </div>
            <div class="modal-field">
                <label for="modal-url">URL</label>
                <input type="text" id="modal-url" placeholder="https://example.com">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-cancel" onclick="closeEditModal()">キャンセル</button>
            <button class="btn-modal-save"   onclick="saveEditModal()">保存</button>
        </div>
    </div>
</div>

<!-- ── 確認ダイアログ ── -->
<div class="confirm-overlay" id="confirmDialog" onclick="if(event.target===this)closeConfirm()">
    <div class="confirm-box">
        <div class="confirm-box-header" id="confirm-title">削除の確認</div>
        <div class="confirm-box-body"   id="confirm-msg">本当に削除しますか？</div>
        <div class="confirm-box-footer">
            <button class="btn-confirm-cancel" onclick="closeConfirm()">キャンセル</button>
            <button class="btn-confirm-ok"     id="confirm-ok-btn"     onclick="doConfirm()">削除する</button>
        </div>
    </div>
</div>

<script>
const GID = <?php echo json_encode($current_gid); ?>;

/* ══════════════════════════════════
   アコーディオン
══════════════════════════════════ */
function toggleBox(title) {
    title.classList.toggle('collapsed');
    title.nextElementSibling.classList.toggle('hidden');
}

/* ══════════════════════════════════
   タブ切り替え
══════════════════════════════════ */
function switchTab(btn, paneId) {
    btn.closest('.add-tab-bar').querySelectorAll('.add-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    btn.closest('.menu-add-box-body').querySelectorAll('.add-tab-pane').forEach(p => p.classList.remove('active'));
    document.getElementById(paneId).classList.add('active');
}

/* ══════════════════════════════════
   チェックリストから追加
══════════════════════════════════ */
function addCheckedItems(...listIds) {
    listIds.forEach(id => {
        const box = document.getElementById(id);
        if (!box) return;
        box.querySelectorAll('input.add-check:checked').forEach(chk => {
            appendItem(chk.dataset.label, chk.dataset.url);
            chk.checked = false;
        });
    });
}

/* ══════════════════════════════════
   カスタムリンク追加
══════════════════════════════════ */
function addCustomLink() {
    const url   = document.getElementById('custom-url').value.trim();
    const label = document.getElementById('custom-label').value.trim();
    if (!url && !label) { alert('URLまたはリンク文字列を入力してください。'); return; }
    appendItem(label || url, url || '#');
    document.getElementById('custom-url').value = '';
    document.getElementById('custom-label').value = '';
}

/* ══════════════════════════════════
   行を生成してリストに追加
══════════════════════════════════ */
function appendItem(label, url) {
    const idx = Date.now() + Math.random();
    const row = document.createElement('div');
    row.className = 'menu-item-row';
    row.draggable = true;
    row.dataset.label = label;
    row.dataset.url   = url;
    row.innerHTML = `
        <input type="checkbox" class="item-select-chk" onchange="onSelectChange()">
        <span class="drag-handle" title="ドラッグして並び替え">
            <span></span><span></span><span></span>
        </span>
        <span class="item-label-display">${escHtml(label)}</span>
        <span class="item-url-display">${escHtml(url)}</span>
        <div class="item-actions">
            <button type="button" class="btn-item-edit" onclick="openEditModal(this)">編集</button>
            <button type="button" class="btn-item-del"  onclick="confirmDeleteOne(this)">削除</button>
        </div>
        <input type="hidden" name="menu_groups[${GID}][items][${idx}][label]" value="${escHtml(label)}">
        <input type="hidden" name="menu_groups[${GID}][items][${idx}][url]"   value="${escHtml(url)}">`;
    document.getElementById('items-list').appendChild(row);
    bindDrag(row);
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ══════════════════════════════════
   ドラッグ & ドロップ 並び替え
══════════════════════════════════ */
let dragSrc = null;

function bindDrag(row) {
    row.addEventListener('dragstart', e => {
        dragSrc = row;
        row.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    row.addEventListener('dragend', () => {
        dragSrc = null;
        row.classList.remove('dragging');
        document.querySelectorAll('.menu-item-row').forEach(r => r.classList.remove('drag-over'));
        syncHiddenInputs();
    });
    row.addEventListener('dragover', e => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (row !== dragSrc) row.classList.add('drag-over');
    });
    row.addEventListener('dragleave', () => row.classList.remove('drag-over'));
    row.addEventListener('drop', e => {
        e.preventDefault();
        row.classList.remove('drag-over');
        if (!dragSrc || dragSrc === row) return;
        const list  = document.getElementById('items-list');
        const rows  = [...list.querySelectorAll('.menu-item-row')];
        const srcIdx = rows.indexOf(dragSrc);
        const tgtIdx = rows.indexOf(row);
        if (srcIdx < tgtIdx) row.after(dragSrc);
        else row.before(dragSrc);
    });
}

/* ドロップ後に hidden input の name インデックスを振り直す */
function syncHiddenInputs() {
    document.querySelectorAll('#items-list .menu-item-row').forEach((row, i) => {
        row.querySelectorAll('input[type="hidden"]').forEach(h => {
            h.name = h.name.replace(/\[items\]\[[\d.]+\]/, `[items][${i}]`);
        });
    });
}

/* 既存行にドラッグを付与 */
document.querySelectorAll('#items-list .menu-item-row').forEach(bindDrag);

/* ══════════════════════════════════
   編集モーダル
══════════════════════════════════ */
let editingRow = null;

function openEditModal(btn) {
    editingRow = btn.closest('.menu-item-row');
    document.getElementById('modal-label').value = editingRow.dataset.label || '';
    document.getElementById('modal-url').value   = editingRow.dataset.url   || '';
    document.getElementById('editModal').classList.add('open');
    setTimeout(() => document.getElementById('modal-label').focus(), 50);
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('open');
    editingRow = null;
}

function saveEditModal() {
    if (!editingRow) return;
    const label = document.getElementById('modal-label').value.trim();
    const url   = document.getElementById('modal-url').value.trim();
    if (!label) { document.getElementById('modal-label').focus(); return; }
    editingRow.dataset.label = label;
    editingRow.dataset.url   = url;
    editingRow.querySelector('.item-label-display').textContent = label;
    editingRow.querySelector('.item-url-display').textContent   = url;
    editingRow.querySelectorAll('input[type="hidden"]').forEach(h => {
        if (h.name.endsWith('[label]')) h.value = label;
        if (h.name.endsWith('[url]'))   h.value = url;
    });
    closeEditModal();
}

document.getElementById('editModal').addEventListener('keydown', e => {
    if (e.key === 'Enter')  saveEditModal();
    if (e.key === 'Escape') closeEditModal();
});

/* ══════════════════════════════════
   確認ダイアログ
══════════════════════════════════ */
let confirmCallback = null;

function openConfirm(title, msg, cb) {
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-msg').textContent   = msg;
    confirmCallback = cb;
    document.getElementById('confirmDialog').classList.add('open');
}

function closeConfirm() {
    document.getElementById('confirmDialog').classList.remove('open');
    confirmCallback = null;
}

function doConfirm() {
    closeConfirm();
    if (confirmCallback) confirmCallback();
}

document.getElementById('confirmDialog').addEventListener('keydown', e => {
    if (e.key === 'Escape') closeConfirm();
});

/* 1件削除 */
function confirmDeleteOne(btn) {
    const row   = btn.closest('.menu-item-row');
    const label = row.dataset.label || '(無題)';
    openConfirm(
        '項目の削除',
        `「${label}」を削除しますか？この操作は保存するまで確定されません。`,
        () => { row.remove(); onSelectChange(); }
    );
}

/* ══════════════════════════════════
   複数選択 & 一括削除
══════════════════════════════════ */
function onSelectChange() {
    const checked = document.querySelectorAll('#items-list .item-select-chk:checked');
    const bar = document.getElementById('bulk-bar');
    if (checked.length > 0) {
        bar.classList.add('visible');
        document.getElementById('bulk-count-label').textContent = `${checked.length}件 選択中`;
    } else {
        bar.classList.remove('visible');
    }
}

function clearSelection() {
    document.querySelectorAll('#items-list .item-select-chk:checked').forEach(c => { c.checked = false; });
    onSelectChange();
}

function bulkDeleteSelected() {
    const checked = document.querySelectorAll('#items-list .item-select-chk:checked');
    if (!checked.length) return;
    openConfirm(
        '複数項目の削除',
        `選択した ${checked.length} 件の項目を削除しますか？この操作は保存するまで確定されません。`,
        () => { checked.forEach(chk => chk.closest('.menu-item-row').remove()); onSelectChange(); }
    );
}

/* ══════════════════════════════════
   新規メニュー作成
══════════════════════════════════ */
function createNewMenu() {
    const name = prompt('メニュー名を入力:');
    if (!name) return;
    const gid = 'm_' + Date.now();
    const fd = new FormData();
    fd.append('action', 'save_menus');
    fd.append(`menu_groups[${gid}][name]`, name);
    fetch('api.php', { method: 'POST', body: fd }).then(() => location.href = '?gid=' + gid);
}

/* ══════════════════════════════════
   保存
══════════════════════════════════ */
document.getElementById('menuForm').addEventListener('submit', function(e) {
    e.preventDefault();
    syncHiddenInputs();
    fetch('api.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(d => {
            if (d.success) { alert('保存しました'); location.reload(); }
            else { alert('エラー: ' + (d.message || '不明')); }
        });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>