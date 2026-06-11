<?php
// admin/edit-posts.php
require_once __DIR__ . '/auth.php';

// 未認証時はダッシュボードへ強制リダイレクト（ログインを挟むため）
if (!check_raptio_auth()) {
    header('Location: index.php');
    exit;
}

$page_title = '投稿一覧';
$current_page = 'posts';
$sub_page = 'list';

$index_data = file_exists(INDEX_FILE) ? json_decode(file_get_contents(INDEX_FILE), true) : [];
$all_posts = is_array($index_data) ? $index_data : [];

// ステータスごとの件数をカウント
$counts = ['all' => 0, 'public' => 0, 'draft' => 0, 'trash' => 0];
foreach ($all_posts as $p) {
    $status = $p['status'] ?? 'public';
    if ($status === 'trash') {
        $counts['trash']++;
    } else {
        $counts['all']++;
        if ($status === 'public') $counts['public']++;
        if ($status === 'draft') $counts['draft']++;
    }
}

// 現在の表示フィルターを取得（デフォルトはゴミ箱以外すべて）
$current_status = $_GET['post_status'] ?? 'all';

// 表示する記事をフィルタリング
$posts = [];
foreach ($all_posts as $p) {
    $status = $p['status'] ?? 'public';
    if ($current_status === 'trash') {
        if ($status === 'trash') $posts[] = $p;
    } else {
        if ($status !== 'trash') {
            if ($current_status === 'all' || $status === $current_status) {
                $posts[] = $p;
            }
        }
    }
}

// ---- フィルター用データ収集 ----
$all_categories = [];
$all_tags = [];
$all_users = [];
foreach ($all_posts as $p) {
    if (!empty($p['category'])) {
        $cat = $p['category'];
        if (!in_array($cat, $all_categories)) $all_categories[] = $cat;
    }
    if (!empty($p['tags']) && is_array($p['tags'])) {
        foreach ($p['tags'] as $tag) {
            if (!in_array($tag, $all_tags)) $all_tags[] = $tag;
        }
    }
    if (!empty($p['author'])) {
        $author = $p['author'];
        if (!in_array($author, $all_users)) $all_users[] = $author;
    }
}
sort($all_categories);
sort($all_tags);
sort($all_users);

// ---- フィルター適用 ----
$filter_date     = $_GET['m']        ?? '';
$filter_category = $_GET['cat']      ?? '';
$filter_tag      = $_GET['tag']      ?? '';
$filter_user     = $_GET['author']   ?? '';
$search_query    = trim($_GET['s']   ?? '');

if ($filter_date !== '') {
    $posts = array_filter($posts, function($p) use ($filter_date) {
        return isset($p['date']) && strpos($p['date'], $filter_date) === 0;
    });
}
if ($filter_category !== '') {
    $posts = array_filter($posts, function($p) use ($filter_category) {
        return ($p['category'] ?? '') === $filter_category;
    });
}
if ($filter_tag !== '') {
    $posts = array_filter($posts, function($p) use ($filter_tag) {
        return isset($p['tags']) && is_array($p['tags']) && in_array($filter_tag, $p['tags']);
    });
}
if ($filter_user !== '') {
    $posts = array_filter($posts, function($p) use ($filter_user) {
        return ($p['author'] ?? '') === $filter_user;
    });
}
if ($search_query !== '') {
    $posts = array_filter($posts, function($p) use ($search_query) {
        return mb_stripos($p['title'] ?? '', $search_query) !== false
            || mb_stripos($p['slug']  ?? '', $search_query) !== false;
    });
}
$posts = array_values($posts);

// ---- ページネーション ----
$per_page    = 20;
$total_items = count($posts);
$total_pages = max(1, (int)ceil($total_items / $per_page));
$current_page_num = max(1, min((int)($_GET['paged'] ?? 1), $total_pages));
$posts = array_slice($posts, ($current_page_num - 1) * $per_page, $per_page);

// ---- 月別リスト生成 ----
$month_list = [];
foreach ($all_posts as $p) {
    if (!empty($p['date'])) {
        $ym = substr($p['date'], 0, 7); // "YYYY-MM"
        if (!isset($month_list[$ym])) $month_list[$ym] = 0;
        $month_list[$ym]++;
    }
}
krsort($month_list);

// ---- クエリ文字列ビルダー（ページネーション用） ----
function build_query(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="wp-content-title-area">
    <h2>投稿</h2>
    <a href="editor.php" class="button-primary">新規追加</a>
    <form method="get" action="edit-posts.php" class="search-form">
        <?php if ($current_status !== 'all'): ?>
            <input type="hidden" name="post_status" value="<?= htmlspecialchars($current_status) ?>">
        <?php endif; ?>
        <input type="search" name="s" value="<?= htmlspecialchars($search_query) ?>" placeholder="投稿を検索" class="search-input">
        <button type="submit" class="button">投稿を検索</button>
    </form>
</div>

<ul class="subsubsub">
    <li>
        <a href="edit-posts.php" class="<?php echo $current_status === 'all' ? 'current' : ''; ?>">
            すべて <span class="count">(<?php echo $counts['all']; ?>)</span>
        </a>
    </li>
    <li>
        <span class="separator">|</span>
        <a href="edit-posts.php?post_status=public" class="<?php echo $current_status === 'public' ? 'current' : ''; ?>">
            公開済み <span class="count">(<?php echo $counts['public']; ?>)</span>
        </a>
    </li>
    <li>
        <span class="separator">|</span>
        <a href="edit-posts.php?post_status=draft" class="<?php echo $current_status === 'draft' ? 'current' : ''; ?>">
            下書き <span class="count">(<?php echo $counts['draft']; ?>)</span>
        </a>
    </li>
    <?php if ($counts['trash'] > 0): ?>
    <li>
        <span class="separator">|</span>
        <a href="edit-posts.php?post_status=trash" class="<?php echo $current_status === 'trash' ? 'current' : ''; ?>" style="<?php echo $current_status === 'trash' ? '' : 'color:#b32d2e;'; ?>">
            ゴミ箱 <span class="count">(<?php echo $counts['trash']; ?>)</span>
        </a>
    </li>
    <?php endif; ?>
</ul>

<div class="tablenav top">
    <div class="tablenav-left">
        <select id="bulk-action-selector">
            <option value="-1">一括操作</option>
            <?php if ($current_status === 'trash'): ?>
                <option value="restore">復元</option>
                <option value="delete_permanently">完全に削除</option>
            <?php else: ?>
                <option value="trash">ゴミ箱へ移動</option>
            <?php endif; ?>
        </select>
        <button onclick="applyBulkAction()" class="button">適用</button>

        <!-- 日付フィルター -->
        <select name="m" id="filter-month" onchange="applyFilters()">
            <option value="">すべての日付</option>
            <?php foreach ($month_list as $ym => $cnt):
                [$y, $mo] = explode('-', $ym);
                $label = $y . '年' . intval($mo) . '月 (' . $cnt . ')';
            ?>
            <option value="<?= htmlspecialchars($ym) ?>" <?= $filter_date === $ym ? 'selected' : '' ?>>
                <?= $label ?>
            </option>
            <?php endforeach; ?>
        </select>

        <!-- カテゴリーフィルター -->
        <select name="cat" id="filter-cat" onchange="applyFilters()">
            <option value="">カテゴリー一覧</option>
            <?php foreach ($all_categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $filter_category === $cat ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <!-- タグフィルター -->
        <select name="tag" id="filter-tag" onchange="applyFilters()">
            <option value="">すべてのタグ</option>
            <?php foreach ($all_tags as $tag): ?>
            <option value="<?= htmlspecialchars($tag) ?>" <?= $filter_tag === $tag ? 'selected' : '' ?>>
                <?= htmlspecialchars($tag) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <!-- ユーザーフィルター -->
        <select name="author" id="filter-author" onchange="applyFilters()">
            <option value="">すべてのユーザー</option>
            <?php foreach ($all_users as $u): ?>
            <option value="<?= htmlspecialchars($u) ?>" <?= $filter_user === $u ? 'selected' : '' ?>>
                <?= htmlspecialchars($u) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <button onclick="applyFilters()" class="button">絞り込み</button>
    </div>

    <!-- 件数＋ページネーション -->
    <div class="tablenav-right">
        <span class="displaying-num"><?= $total_items ?>個の項目</span>
        <span class="pagination-links">
            <a class="first-page button<?= $current_page_num <= 1 ? ' disabled' : '' ?>"
               href="?<?= build_query(['paged' => 1]) ?>">«</a>
            <a class="prev-page button<?= $current_page_num <= 1 ? ' disabled' : '' ?>"
               href="?<?= build_query(['paged' => max(1, $current_page_num - 1)]) ?>">‹</a>
            <span class="paging-input">
                <input type="number" class="current-page" value="<?= $current_page_num ?>"
                       min="1" max="<?= $total_pages ?>"
                       onchange="gotoPage(this.value)">
                / <?= $total_pages ?>
            </span>
            <a class="next-page button<?= $current_page_num >= $total_pages ? ' disabled' : '' ?>"
               href="?<?= build_query(['paged' => min($total_pages, $current_page_num + 1)]) ?>">›</a>
            <a class="last-page button<?= $current_page_num >= $total_pages ? ' disabled' : '' ?>"
               href="?<?= build_query(['paged' => $total_pages]) ?>">»</a>
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
            <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:30px; color:#646970;">投稿が見つかりません。</td>
                </tr>
            <?php else: foreach ($posts as $post): ?>
                <tr>
                    <td style="text-align: center; vertical-align: middle;">
                        <input type="checkbox" name="post_ids[]" value="<?php echo $post['id']; ?>" class="post-checkbox">
                    </td>
                    
                    <td style="text-align: center; vertical-align: middle; padding: 8px 0;">
                        <?php if (!empty($post['thumbnail'])): ?>
                            <img src="../<?php echo htmlspecialchars($post['thumbnail'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 2px; border: 1px solid #dcdcde; display: block; margin: 0 auto;">
                        <?php else: ?>
                            <div style="width: 40px; height: 40px; background: #f6f7f7; border-radius: 2px; border: 1px solid #dcdcde; display: flex; align-items: center; justify-content: center; color: #c3c4c7; font-size: 9px; margin: 0 auto; user-select: none; font-weight: 600;">—</div>
                        <?php endif; ?>
                    </td>
                    
                    <td class="post-title-block" style="vertical-align: middle;">
                        <a href="editor.php?id=<?php echo $post['id']; ?>" class="post-title-link">
                            <?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <?php if ($post['status'] === 'draft'): ?>
                            <span class="wp-badge-inline">— 下書き</span>
                        <?php elseif ($post['status'] === 'trash'): ?>
                            <span class="wp-badge-inline">— ゴミ箱</span>
                        <?php endif; ?>
                        
                        <div class="row-actions">
                            <?php if ($post['status'] === 'trash'): ?>
                                <button onclick="changeStatus('<?php echo $post['id']; ?>', 'restore')">復元</button> | 
                                <button onclick="deletePostPermanently('<?php echo $post['id']; ?>')" class="trash-action">完全に削除</button>
                            <?php else: ?>
                                <a href="editor.php?id=<?php echo $post['id']; ?>">編集</a> | 
                                <button onclick="changeStatus('<?php echo $post['id']; ?>', 'trash')" class="trash-action">ゴミ箱へ移動</button>
                            <?php endif; ?>
                        </div>
                    </td>
                    
                    <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;" title="<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="slug-code"><?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </td>
                    
                    <td style="vertical-align: middle; color: #50575e;">
                        <?php 
                            $ts = strtotime($post['date'] ?? '');
                            echo $ts ? date('Y/m/d', $ts) : htmlspecialchars($post['date'] ?? '', ENT_QUOTES, 'UTF-8');
                        ?>
                        <br>
                        <span style="font-size:11px; color:#646970;">
                            <?php echo $post['status'] === 'public' ? '公開済' : '最終編集'; ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script>
    // フィルタードロップダウン変更時にURLへ反映
    function applyFilters() {
        const params = new URLSearchParams(window.location.search);
        params.set('m',      document.getElementById('filter-month')?.value  ?? '');
        params.set('cat',    document.getElementById('filter-cat')?.value    ?? '');
        params.set('tag',    document.getElementById('filter-tag')?.value    ?? '');
        params.set('author', document.getElementById('filter-author')?.value ?? '');
        params.delete('paged'); // フィルター変更時は1ページ目へ
        // 空値は削除
        for (const [k, v] of [...params.entries()]) { if (!v) params.delete(k); }
        window.location.search = params.toString();
    }

    // ページ番号直接入力
    function gotoPage(val) {
        const n = parseInt(val, 10);
        if (isNaN(n)) return;
        const params = new URLSearchParams(window.location.search);
        params.set('paged', n);
        window.location.search = params.toString();
    }

    // 全選択・全解除の連動
    document.getElementById('cb-select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.post-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    // 単発のステータス変更（ゴミ箱へ移動 / 復元）
    function changeStatus(id, action) {
        let msg = action === 'trash' ? 'この記事をゴミ箱に移動しますか？' : 'この記事を復元しますか？';
        if (confirm(msg)) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('id', id);
            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '処理に失敗しました。');
                }
            })
            .catch(err => {
                console.error(err);
                alert('通信エラーが発生しました。');
            });
        }
    }

    // 単発の完全削除
    function deletePostPermanently(id) {
        if (confirm('この記事を完全に削除しますか？この操作は取り消せません。')) {
            const formData = new FormData();
            formData.append('action', 'delete_permanently');
            formData.append('id', id);
            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '削除に失敗しました。');
                }
            })
            .catch(err => {
                console.error(err);
                alert('通信エラーが発生しました。');
            });
        }
    }

    // 一括操作の適用
    function applyBulkAction() {
        const selector = document.getElementById('bulk-action-selector');
        const action = selector.value;
        if (action === '-1') {
            alert('操作を選択してください。');
            return;
        }

        const checkedBoxes = document.querySelectorAll('.post-checkbox:checked');
        if (checkedBoxes.length === 0) {
            alert('選択された記事がありません。');
            return;
        }

        let confirmMsg = '選択した記事に対して処理を実行しますか？';
        if (action === 'delete_permanently') {
            confirmMsg = '選択した記事を完全に削除しますか？この操作は取り消せません。';
        }

        if (confirm(confirmMsg)) {
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            const formData = new FormData();
            formData.append('action', 'bulk_' + action);
            formData.append('ids', JSON.stringify(ids));

            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '一括処理に失敗しました。');
                }
            })
            .catch(err => {
                console.error(err);
                alert('通信エラーが発生しました。');
            });
        }
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';