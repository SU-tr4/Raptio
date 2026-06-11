<?php
// admin/edit-pages.php
require_once __DIR__ . '/auth.php';

if (!check_raptio_auth()) {
    header('Location: index.php');
    exit;
}

$page_title   = '単独ページ一覧';
$current_page = 'pages';
$sub_page     = 'list';

$index_data = file_exists(PAGES_INDEX_FILE) ? json_decode(file_get_contents(PAGES_INDEX_FILE), true) : [];
$all_pages  = is_array($index_data) ? $index_data : [];

// ステータスごとの件数をカウント
$counts = ['all' => 0, 'public' => 0, 'draft' => 0, 'trash' => 0];
foreach ($all_pages as $p) {
    $status = $p['status'] ?? 'draft';
    if ($status === 'trash') {
        $counts['trash']++;
    } else {
        $counts['all']++;
        if ($status === 'public') $counts['public']++;
        if ($status === 'draft')  $counts['draft']++;
    }
}

// 現在の表示フィルター
$current_status = $_GET['post_status'] ?? 'all';

// フィルタリング
$pages = [];
foreach ($all_pages as $p) {
    $status = $p['status'] ?? 'draft';
    if ($current_status === 'trash') {
        if ($status === 'trash') $pages[] = $p;
    } else {
        if ($status !== 'trash') {
            if ($current_status === 'all' || $status === $current_status) {
                $pages[] = $p;
            }
        }
    }
}

// 検索
$search_query = trim($_GET['s'] ?? '');
if ($search_query !== '') {
    $pages = array_filter($pages, function($p) use ($search_query) {
        return mb_stripos($p['title'] ?? '', $search_query) !== false
            || mb_stripos($p['slug']  ?? '', $search_query) !== false;
    });
}
$pages = array_values($pages);

// ---- 日付（date）の新しい順（降順）にソート ----
usort($pages, function($a, $b) {
    $dateA = $a['date'] ?? '';
    $dateB = $b['date'] ?? '';
    return strcmp($dateB, $dateA);
});

// ページネーション
$per_page         = 20;
$total_items      = count($pages);
$total_pages      = max(1, (int)ceil($total_items / $per_page));
$current_page_num = max(1, min((int)($_GET['paged'] ?? 1), $total_pages));
$pages            = array_slice($pages, ($current_page_num - 1) * $per_page, $per_page);

// 月別リスト
$month_list = [];
foreach ($all_pages as $p) {
    if (!empty($p['date'])) {
        $ym = substr($p['date'], 0, 7);
        if (!isset($month_list[$ym])) $month_list[$ym] = 0;
        $month_list[$ym]++;
    }
}
krsort($month_list);

function build_page_query(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="wp-content-title-area">
    <h2>単独ページ</h2>
    <a href="editor.php?mode=page" class="button-primary">新規追加</a>
    <form method="get" action="edit-pages.php" class="search-form">
        <?php if ($current_status !== 'all'): ?>
            <input type="hidden" name="post_status" value="<?= htmlspecialchars($current_status) ?>">
        <?php endif; ?>
        <input type="search" name="s" value="<?= htmlspecialchars($search_query) ?>" placeholder="ページを検索" class="search-input">
        <button type="submit" class="button">ページを検索</button>
    </form>
</div>

<ul class="subsubsub">
    <li>
        <a href="edit-pages.php" class="<?= $current_status === 'all' ? 'current' : '' ?>">
            すべて <span class="count">(<?= $counts['all'] ?>)</span>
        </a>
    </li>
    <li>
        <span class="separator">|</span>
        <a href="edit-pages.php?post_status=public" class="<?= $current_status === 'public' ? 'current' : '' ?>">
            公開済み <span class="count">(<?= $counts['public'] ?>)</span>
        </a>
    </li>
    <li>
        <span class="separator">|</span>
        <a href="edit-pages.php?post_status=draft" class="<?= $current_status === 'draft' ? 'current' : '' ?>">
            下書き <span class="count">(<?= $counts['draft'] ?>)</span>
        </a>
    </li>
    <?php if ($counts['trash'] > 0): ?>
    <li>
        <span class="separator">|</span>
        <a href="edit-pages.php?post_status=trash" class="<?= $current_status === 'trash' ? 'current' : '' ?>" style="<?= $current_status === 'trash' ? '' : 'color:#b32d2e;' ?>">
            ゴミ箱 <span class="count">(<?= $counts['trash'] ?>)</span>
        </a>
    </li>
    <?php endif; ?>
</ul>

<div class="tablenav top">
    <div class="tablenav-left">
        <select id="bulk-action-selector">
            <option value="-1">一括操作</option>
            <?php if ($current_status === 'trash'): ?>
                <option value="restore_page">復元</option>
                <option value="delete_page_permanently">完全に削除</option>
            <?php else: ?>
                <option value="trash_page">ゴミ箱へ移動</option>
            <?php endif; ?>
        </select>
        <button onclick="applyBulkAction()" class="button">適用</button>

        <select name="m" id="filter-month" onchange="applyFilters()">
            <option value="">すべての日付</option>
            <?php foreach ($month_list as $ym => $cnt):
                [$y, $mo] = explode('-', $ym);
                $label = $y . '年' . intval($mo) . '月 (' . $cnt . ')';
                $filter_date = $_GET['m'] ?? '';
            ?>
            <option value="<?= htmlspecialchars($ym) ?>" <?= $filter_date === $ym ? 'selected' : '' ?>>
                <?= $label ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="tablenav-right">
        <span class="displaying-num"><?= $total_items ?> 件</span>
        <span class="pagination-links">
            <a class="first-page button<?= $current_page_num <= 1 ? ' disabled' : '' ?>"
               href="?<?= build_page_query(['paged' => 1]) ?>">«</a>
            <a class="prev-page button<?= $current_page_num <= 1 ? ' disabled' : '' ?>"
               href="?<?= build_page_query(['paged' => max(1, $current_page_num - 1)]) ?>">‹</a>
            <span class="paging-input">
                <input type="number" class="current-page" value="<?= $current_page_num ?>"
                       min="1" max="<?= $total_pages ?>"
                       onchange="gotoPage(this.value)" style="width:50px; text-align:center;">
                / <span class="total-pages"><?= $total_pages ?></span>
            </span>
            <a class="next-page button<?= $current_page_num >= $total_pages ? ' disabled' : '' ?>"
               href="?<?= build_page_query(['paged' => min($total_pages, $current_page_num + 1)]) ?>">›</a>
            <a class="last-page button<?= $current_page_num >= $total_pages ? ' disabled' : '' ?>"
               href="?<?= build_page_query(['paged' => $total_pages]) ?>">»</a>
        </span>
    </div>
</div>

<div class="wp-list-table-container">
    <table class="wp-list-table">
        <thead>
            <tr>
                <th style="width: 30px; text-align: center;"><input type="checkbox" id="cb-select-all"></th>
                <th style="width: 52px; text-align: center;">画像</th>
                <th>タイトル</th>
                <th style="width: 220px;">スラッグ</th>
                <th style="width: 140px;">日付</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pages)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:30px; color:#646970;">単独ページが見つかりません。</td>
                </tr>
            <?php else: foreach ($pages as $page): ?>
                <tr>
                    <td style="text-align: center; vertical-align: middle;">
                        <input type="checkbox" name="page_ids[]" value="<?= $page['id'] ?>" class="page-checkbox">
                    </td>

                    <td style="text-align: center; vertical-align: middle; padding: 8px 0;">
                        <?php if (!empty($page['thumbnail'])): ?>
                            <img src="../<?= htmlspecialchars($page['thumbnail'], ENT_QUOTES, 'UTF-8') ?>" alt=""
                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 2px; border: 1px solid #dcdcde; display: block; margin: 0 auto;">
                        <?php else: ?>
                            <div style="width: 40px; height: 40px; background: #f6f7f7; border-radius: 2px; border: 1px solid #dcdcde; display: flex; align-items: center; justify-content: center; color: #c3c4c7; font-size: 9px; margin: 0 auto; user-select: none; font-weight: 600;">—</div>
                        <?php endif; ?>
                    </td>

                    <td class="post-title-block" style="vertical-align: middle;">
                        <a href="editor.php?mode=page&id=<?= $page['id'] ?>" class="post-title-link">
                            <?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php if (($page['status'] ?? '') === 'draft'): ?>
                            <span class="wp-badge-inline">— 下書き</span>
                        <?php elseif (($page['status'] ?? '') === 'trash'): ?>
                            <span class="wp-badge-inline">— ゴミ箱</span>
                        <?php endif; ?>

                        <div class="row-actions">
                            <?php if (($page['status'] ?? '') === 'trash'): ?>
                                <button onclick="changeStatus('<?= $page['id'] ?>', 'restore_page')">復元</button> |
                                <button onclick="deletePagePermanently('<?= $page['id'] ?>')" class="trash-action">完全に削除</button>
                            <?php else: ?>
                                <a href="editor.php?mode=page&id=<?= $page['id'] ?>">編集</a> |
                                <button onclick="changeStatus('<?= $page['id'] ?>', 'trash_page')" class="trash-action">ゴミ箱へ移動</button>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;"
                        title="<?= htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="slug-code"><?= htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>

                    <td style="vertical-align: middle; color: #50575e;">
                        <?php
                            $ts = strtotime($page['date'] ?? '');
                            echo $ts ? date('Y/m/d', $ts) : htmlspecialchars($page['date'] ?? '', ENT_QUOTES, 'UTF-8');
                        ?>
                        <br>
                        <span style="font-size:11px; color:#646970;">
                            <?= ($page['status'] ?? '') === 'public' ? '公開済' : '最終編集' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script>
    function applyFilters() {
        const params = new URLSearchParams(window.location.search);
        params.set('m', document.getElementById('filter-month')?.value ?? '');
        params.delete('paged');
        for (const [k, v] of [...params.entries()]) { if (!v) params.delete(k); }
        window.location.search = params.toString();
    }

    function gotoPage(val) {
        const n = parseInt(val, 10);
        if (isNaN(n)) return;
        const params = new URLSearchParams(window.location.search);
        params.set('paged', n);
        window.location.search = params.toString();
    }

    document.getElementById('cb-select-all').addEventListener('change', function() {
        document.querySelectorAll('.page-checkbox').forEach(cb => cb.checked = this.checked);
    });

    function changeStatus(id, action) {
        const msg = (action === 'trash_page') ? 'このページをゴミ箱に移動しますか？' : 'このページを復元しますか？';
        if (!confirm(msg)) return;
        const fd = new FormData();
        fd.append('action', action);
        fd.append('id', id);
        fetch('api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => { if (d.success) location.reload(); else alert(d.message || '処理に失敗しました。'); })
            .catch(() => alert('通信エラーが発生しました。'));
    }

    function deletePagePermanently(id) {
        if (!confirm('このページを完全に削除しますか？この操作は取り消せません。')) return;
        const fd = new FormData();
        fd.append('action', 'delete_page_permanently');
        fd.append('id', id);
        fetch('api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => { if (d.success) location.reload(); else alert(d.message || '削除に失敗しました。'); })
            .catch(() => alert('通信エラーが発生しました。'));
    }

    function applyBulkAction() {
        const action = document.getElementById('bulk-action-selector').value;
        if (action === '-1') { alert('操作を選択してください。'); return; }
        const checked = document.querySelectorAll('.page-checkbox:checked');
        if (checked.length === 0) { alert('選択されたページがありません。'); return; }

        let msg = '選択したページに対して処理を実行しますか？';
        if (action === 'delete_page_permanently') msg = '選択したページを完全に削除しますか？この操作は取り消せません。';
        if (!confirm(msg)) return;

        const ids = Array.from(checked).map(cb => cb.value);
        const fd = new FormData();
        fd.append('action', 'bulk_' + action);
        fd.append('ids', JSON.stringify(ids));
        fetch('api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => { if (d.success) location.reload(); else alert(d.message || '一括処理に失敗しました。'); })
            .catch(() => alert('通信エラーが発生しました。'));
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>