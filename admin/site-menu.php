<?php
// admin/site-menu.php
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }

$config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
$menus       = $config_data['menus'] ?? [];
$locations   = $config_data['menu_locations'] ?? [];

// メニュー項目追加パネル用データ取得
$categories  = file_exists(CATEGORY_FILE)    ? json_decode(file_get_contents(CATEGORY_FILE),    true) : [];
$posts_index = file_exists(INDEX_FILE)       ? json_decode(file_get_contents(INDEX_FILE),        true) : [];
$pages_index = file_exists(PAGES_INDEX_FILE) ? json_decode(file_get_contents(PAGES_INDEX_FILE),  true) : [];
$cpt_config  = file_exists(CPT_CONFIG_FILE)  ? json_decode(file_get_contents(CPT_CONFIG_FILE),   true) : [];

// カスタム投稿インデックスを収集
$cpt_posts = [];
foreach ($cpt_config as $cpt_slug => $cpt_info) {
    $cpt_index_file = DATA_DIR . "/posts_{$cpt_slug}_index.json";
    if (file_exists($cpt_index_file)) {
        $cpt_items = json_decode(file_get_contents($cpt_index_file), true) ?? [];
        foreach ($cpt_items as $item) {
            if (($item['status'] ?? '') === 'publish') {
                $cpt_posts[] = array_merge($item, ['_cpt_label' => $cpt_info['label'] ?? $cpt_slug]);
            }
        }
    }
}

$published_posts = array_filter($posts_index, fn($p) => ($p['status'] ?? '') === 'publish');
$published_pages = array_filter($pages_index, fn($p) => ($p['status'] ?? '') === 'publish');

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
/* ── メニュー管理レイアウト ─────────────────────────── */
.menu-manager-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 24px;
    align-items: start;
}
/* 左パネル */
.menu-list-panel { display: flex; flex-direction: column; gap: 16px; }

/* 追加ボックス共通 */
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

/* タブ */
.add-tab-bar { display: flex; gap: 0; border-bottom: 1px solid #c3c4c7; margin-bottom: 8px; }
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
}
.add-tab.active { color: #2271b1; border-bottom-color: #2271b1; }

/* タブコンテンツ */
.add-tab-pane { display: none; }
.add-tab-pane.active { display: block; }

/* チェックリスト */
.add-checklist {
    max-height: 160px;
    overflow-y: auto;
    border: 1px solid #dcdcde;
    border-radius: 2px;
    margin-bottom: 8px;
}
.add-checklist label {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 5px 8px;
    font-size: 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f1;
}
.add-checklist label:last-child { border-bottom: none; }
.add-checklist label:hover { background: #f0f0f0; }
.add-checklist-empty { padding: 8px; font-size: 12px; color: #888; }

/* カスタムURL用 */
.custom-url-field { display: flex; flex-direction: column; gap: 6px; }
.custom-url-field label { font-size: 12px; font-weight: 600; color: #3c434a; }
.custom-url-field input { padding: 5px 7px; font-size: 12px; border: 1px solid #c3c4c7; border-radius: 2px; width: 100%; }

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
}
.btn-add-to-menu:hover { background: #135e96; }

/* 右パネル：既存アイテム行 */
.menu-item-row {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 0;
    border-bottom: 1px solid #f0f0f1;
}
.menu-item-row:last-child { border-bottom: none; }
.menu-item-row input { padding: 5px 7px; font-size: 13px; border: 1px solid #c3c4c7; border-radius: 2px; }
</style>

<div class="wp-content-title-area"><h2>メニュー管理</h2></div>

<div class="menu-manager-layout">

    <!-- ════ 左パネル ════ -->
    <div class="menu-list-panel">

        <!-- メニューセット一覧 -->
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

        <!-- 投稿を追加 -->
        <div class="menu-add-box">
            <div class="menu-add-box-title collapsed" onclick="toggleBox(this)">
                投稿 <span class="toggle-arrow">▼</span>
            </div>
            <div class="menu-add-box-body hidden">
                <div class="add-tab-bar">
                    <button type="button" class="add-tab active" onclick="switchTab(this,'tab-post-recent')">最近</button>
                    <button type="button" class="add-tab" onclick="switchTab(this,'tab-post-all')">すべて</button>
                </div>
                <!-- 最近 -->
                <div id="tab-post-recent" class="add-tab-pane active">
                    <div class="add-checklist" id="checklist-post-recent">
                        <?php
                        $recent = array_slice(array_reverse(array_values($published_posts)), 0, 10);
                        if ($recent): foreach ($recent as $p): ?>
                        <label>
                            <input type="checkbox" class="add-check" data-label="<?php echo htmlspecialchars($p['title']); ?>" data-url="<?php echo htmlspecialchars('?slug='.$p['slug']); ?>">
                            <?php echo htmlspecialchars($p['title']); ?>
                        </label>
                        <?php endforeach; else: ?>
                        <div class="add-checklist-empty">公開中の投稿がありません</div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- すべて -->
                <div id="tab-post-all" class="add-tab-pane">
                    <div class="add-checklist" id="checklist-post-all">
                        <?php if ($published_posts): foreach ($published_posts as $p): ?>
                        <label>
                            <input type="checkbox" class="add-check" data-label="<?php echo htmlspecialchars($p['title']); ?>" data-url="<?php echo htmlspecialchars('?slug='.$p['slug']); ?>">
                            <?php echo htmlspecialchars($p['title']); ?>
                        </label>
                        <?php endforeach; else: ?>
                        <div class="add-checklist-empty">公開中の投稿がありません</div>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="button" class="btn-add-to-menu" onclick="addCheckedItems('checklist-post-recent','checklist-post-all')">メニューに追加</button>
            </div>
        </div>

        <!-- 固定ページを追加 -->
        <div class="menu-add-box">
            <div class="menu-add-box-title collapsed" onclick="toggleBox(this)">
                固定ページ <span class="toggle-arrow">▼</span>
            </div>
            <div class="menu-add-box-body hidden">
                <div class="add-checklist" id="checklist-pages">
                    <?php if ($published_pages): foreach ($published_pages as $p): ?>
                    <label>
                        <input type="checkbox" class="add-check" data-label="<?php echo htmlspecialchars($p['title']); ?>" data-url="<?php echo htmlspecialchars('?page='.$p['slug']); ?>">
                        <?php echo htmlspecialchars($p['title']); ?>
                    </label>
                    <?php endforeach; else: ?>
                    <div class="add-checklist-empty">公開中のページがありません</div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-add-to-menu" onclick="addCheckedItems('checklist-pages')">メニューに追加</button>
            </div>
        </div>

        <!-- カテゴリーを追加 -->
        <div class="menu-add-box">
            <div class="menu-add-box-title collapsed" onclick="toggleBox(this)">
                カテゴリー <span class="toggle-arrow">▼</span>
            </div>
            <div class="menu-add-box-body hidden">
                <div class="add-checklist" id="checklist-cats">
                    <?php if ($categories): foreach ($categories as $cat): ?>
                    <label>
                        <input type="checkbox" class="add-check" data-label="<?php echo htmlspecialchars($cat['name']); ?>" data-url="<?php echo htmlspecialchars('?category='.$cat['slug']); ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </label>
                    <?php endforeach; else: ?>
                    <div class="add-checklist-empty">カテゴリーがありません</div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-add-to-menu" onclick="addCheckedItems('checklist-cats')">メニューに追加</button>
            </div>
        </div>

        <?php if ($cpt_posts): ?>
        <!-- カスタム投稿を追加 -->
        <div class="menu-add-box">
            <div class="menu-add-box-title collapsed" onclick="toggleBox(this)">
                カスタム投稿 <span class="toggle-arrow">▼</span>
            </div>
            <div class="menu-add-box-body hidden">
                <div class="add-checklist" id="checklist-cpt">
                    <?php foreach ($cpt_posts as $p): ?>
                    <label>
                        <input type="checkbox" class="add-check"
                               data-label="<?php echo htmlspecialchars($p['title']); ?>"
                               data-url="<?php echo htmlspecialchars('?slug='.$p['slug'].'&type='.$p['type']); ?>">
                        <span style="color:#888;font-size:11px;">[<?php echo htmlspecialchars($p['_cpt_label']); ?>]</span>
                        <?php echo htmlspecialchars($p['title']); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-add-to-menu" onclick="addCheckedItems('checklist-cpt')">メニューに追加</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- カスタムリンク -->
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

    </div><!-- /左パネル -->

    <!-- ════ 右パネル：メニュー編集 ════ -->
    <div class="menu-edit-panel">
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

            <div id="items-list" style="margin-bottom:16px;">
                <?php foreach (($current_group['items'] ?? []) as $idx => $item): ?>
                <div class="menu-item-row">
                    <input type="text"
                           name="menu_groups[<?php echo htmlspecialchars($current_gid); ?>][items][<?php echo $idx; ?>][label]"
                           value="<?php echo htmlspecialchars($item['label']); ?>"
                           placeholder="ラベル" style="flex:1;">
                    <input type="text"
                           name="menu_groups[<?php echo htmlspecialchars($current_gid); ?>][items][<?php echo $idx; ?>][url]"
                           value="<?php echo htmlspecialchars($item['url']); ?>"
                           placeholder="URL" style="flex:1.5;">
                    <button type="button" onclick="this.closest('.menu-item-row').remove()" class="button button-link-delete" style="flex-shrink:0;">削除</button>
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

</div><!-- /menu-manager-layout -->

<script>
const GID = <?php echo json_encode($current_gid); ?>;

/* ── アコーディオン ── */
function toggleBox(title) {
    title.classList.toggle('collapsed');
    title.nextElementSibling.classList.toggle('hidden');
}

/* ── タブ切り替え ── */
function switchTab(btn, paneId) {
    btn.closest('.add-tab-bar').querySelectorAll('.add-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    btn.closest('.menu-add-box-body').querySelectorAll('.add-tab-pane').forEach(p => p.classList.remove('active'));
    document.getElementById(paneId).classList.add('active');
}

/* ── チェックリストから追加 ── */
function addCheckedItems(...listIds) {
    const list = document.getElementById('items-list');
    listIds.forEach(id => {
        const box = document.getElementById(id);
        if (!box) return;
        box.querySelectorAll('input.add-check:checked').forEach(chk => {
            appendItem(chk.dataset.label, chk.dataset.url);
            chk.checked = false;
        });
    });
}

/* ── カスタムリンク追加 ── */
function addCustomLink() {
    const url   = document.getElementById('custom-url').value.trim();
    const label = document.getElementById('custom-label').value.trim();
    if (!url && !label) { alert('URLまたはリンク文字列を入力してください。'); return; }
    appendItem(label || url, url || '#');
    document.getElementById('custom-url').value = '';
    document.getElementById('custom-label').value = '';
}

/* ── 行を生成してリストに追加 ── */
function appendItem(label, url) {
    const idx = Date.now() + Math.random();
    const row = document.createElement('div');
    row.className = 'menu-item-row';
    row.innerHTML = `
        <input type="text" name="menu_groups[${GID}][items][${idx}][label]"
               value="${escHtml(label)}" placeholder="ラベル" style="flex:1;">
        <input type="text" name="menu_groups[${GID}][items][${idx}][url]"
               value="${escHtml(url)}" placeholder="URL" style="flex:1.5;">
        <button type="button" onclick="this.closest('.menu-item-row').remove()"
                class="button button-link-delete" style="flex-shrink:0;">削除</button>`;
    document.getElementById('items-list').appendChild(row);
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── 新規メニュー作成 ── */
function createNewMenu() {
    const name = prompt('メニュー名を入力:');
    if (!name) return;
    const gid = 'm_' + Date.now();
    const fd = new FormData();
    fd.append('action', 'save_menus');
    fd.append(`menu_groups[${gid}][name]`, name);
    fetch('api.php', { method: 'POST', body: fd }).then(() => location.href = '?gid=' + gid);
}

/* ── 保存 ── */
document.getElementById('menuForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(d => { if (d.success) { alert('保存しました'); location.reload(); } else { alert('エラー: ' + (d.message||'不明')); } });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>