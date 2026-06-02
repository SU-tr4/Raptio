<?php
// admin/categories.php

require_once __DIR__ . '/auth.php';

// 未認証時はダッシュボードへ強制リダイレクト
if (!check_raptio_auth()) {
    header('Location: index.php');
    exit;
}

$page_title = 'カテゴリー';
$current_page = 'posts';
$sub_page = 'categories';

// カテゴリーデータファイルのパス定義
$categories_file = DATA_DIR . '/categories.json';

// 初期データのロード
$categories = [];
if (file_exists($categories_file)) {
    $raw_data = file_get_contents($categories_file);
    $decoded = json_decode($raw_data, true);
    $categories = is_array($decoded) ? $decoded : [];
}

/**
 * 階層構造用のヘルパー関数
 */
function get_hierarchical_categories($categories, $parent_id = null, $depth = 0) {
    $result = [];
    foreach ($categories as $cat) {
        $cat_parent_id = (isset($cat['parent_id']) && $cat['parent_id'] !== '') ? $cat['parent_id'] : null;
        $target_parent_id = (!empty($parent_id)) ? $parent_id : null;

        if ($cat_parent_id === $target_parent_id) {
            $cat['depth'] = $depth;
            $result[] = $cat;
            $result = array_merge($result, get_hierarchical_categories($categories, $cat['id'], $depth + 1));
        }
    }
    return $result;
}

// メッセージ初期化
$message = '';
$error = '';

// --------------------------------------------------------------------------
// POST処理
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $req_parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : "";

    if ($_POST['action'] === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        
        if ($name === '') {
            $error = '名前を入力してください。';
        } else {
            if ($slug === '') {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $name));
                if ($slug === '') { $slug = 'cat-' . time(); }
            }
            
            foreach ($categories as $cat) {
                if ($cat['slug'] === $slug) {
                    $error = 'このスラッグは既に他で使用されています。';
                    break;
                }
            }
            
            if (empty($error)) {
                $new_id = uniqid('cat_', true);
                $categories[] = [
                    'id' => $new_id,
                    'name' => $name,
                    'slug' => $slug,
                    'parent_id' => $req_parent_id
                ];
                file_put_contents($categories_file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $message = 'カテゴリーを追加しました。';
            }
        }
        
    } elseif ($_POST['action'] === 'edit_category') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        
        if ($id === $req_parent_id) {
            $error = '自分自身を親カテゴリーに設定することはできません。';
        } elseif ($id === '' || $name === '') {
            $error = 'IDまたは名前が正しくありません。';
        } else {
            if ($slug === '') {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $name));
                if ($slug === '') { $slug = 'cat-' . time(); }
            }
            
            $found = false;
            foreach ($categories as &$cat) {
                if ($cat['id'] === $id) {
                    $cat['name'] = $name;
                    $cat['slug'] = $slug;
                    $cat['parent_id'] = $req_parent_id;
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                file_put_contents($categories_file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $message = 'カテゴリーを更新しました。';
            } else {
                $error = 'カテゴリーが見つかりませんでした。';
            }
        }

    } elseif ($_POST['action'] === 'delete_category') {
        $target_id = $_POST['id'] ?? '';
        $filtered = [];
        $found = false;
        
        foreach ($categories as $cat) {
            if ($cat['id'] === $target_id) {
                $found = true;
                continue;
            }
            if (($cat['parent_id'] ?? "") === $target_id) {
                $cat['parent_id'] = "";
            }
            $filtered[] = $cat;
        }
        
        if ($found) {
            $categories = $filtered;
            file_put_contents($categories_file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $message = 'カテゴリーを削除しました。';
        }
        
    } elseif ($_POST['action'] === 'bulk_delete_categories') {
        $selected_ids = $_POST['selected_categories'] ?? [];
        if (!empty($selected_ids) && is_array($selected_ids)) {
            $filtered = [];
            $deleted_count = 0;
            
            foreach ($categories as $cat) {
                if (in_array($cat['id'], $selected_ids, true)) {
                    $deleted_count++;
                    continue;
                }
                if (in_array(($cat['parent_id'] ?? ""), $selected_ids, true)) {
                    $cat['parent_id'] = "";
                }
                $filtered[] = $cat;
            }
            
            $categories = $filtered;
            file_put_contents($categories_file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $message = $deleted_count . ' 件のカテゴリーを削除しました。';
        }
    } elseif ($_POST['action'] === 'update_order') {
        $order_data = json_decode($_POST['order_data'] ?? '[]', true);
        if (is_array($order_data)) {
            $new_categories = [];
            foreach ($order_data as $item) {
                foreach ($categories as $cat) {
                    if ($cat['id'] === $item['id']) {
                        $cat['parent_id'] = $item['parent_id'] ?? "";
                        $new_categories[] = $cat;
                        break;
                    }
                }
            }
            $categories = $new_categories;
            file_put_contents($categories_file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            exit('success');
        }
    }
}

$sorted_categories = get_hierarchical_categories($categories);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="wp-content-title-area">
    <h2>カテゴリー</h2>
</div>

<?php if (!empty($message)): ?>
    <div style="background: #fff; border-left: 4px solid var(--wp-notice-success); padding: 12px; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
        <p style="margin: 0; color: var(--wp-text-main); font-size: 13px;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="background: #fff; border-left: 4px solid var(--wp-danger); padding: 12px; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
        <p style="margin: 0; color: var(--wp-text-main); font-size: 13px;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
<?php endif; ?>

<div style="display: flex; gap: 30px; align-items: flex-start; margin-top: 10px;">
    <div style="flex: 1; min-width: 280px; max-width: 400px;" class="settings-card">
        <h3>新規カテゴリーを追加</h3>
        <form action="categories.php" method="POST" style="margin-top: 15px;">
            <input type="hidden" name="action" value="add_category">
            <div style="margin-bottom: 15px;">
                <label for="tag-name" style="display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327;">名前</label>
                <input type="text" name="name" id="tag-name" required style="width: 100%;">
            </div>
            <div style="margin-bottom: 15px;">
                <label for="tag-parent" style="display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327;">親カテゴリー</label>
                <select name="parent_id" id="tag-parent" style="width: 100%;">
                    <option value="">なし（親カテゴリー）</option>
                    <?php foreach ($sorted_categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo str_repeat('&nbsp;&nbsp;', $cat['depth']) . htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label for="tag-slug" style="display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327;">スラッグ</label>
                <input type="text" name="slug" id="tag-slug" placeholder="例: news" style="width: 100%;">
            </div>
            <button type="submit" class="button button-primary" style="width: 100%; padding: 10px 0;">新規カテゴリーを追加</button>
        </form>
    </div>
    
    <div style="flex: 2;" class="settings-card">
        <form action="categories.php" method="POST" id="bulk_delete_form" onsubmit="return confirm('選択したすべてのカテゴリーを削除しますか？');">
            <input type="hidden" name="action" value="bulk_delete_categories">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0;">登録済みカテゴリー <span style="font-size:12px; color:#666; font-weight:normal;">(ドラッグ＆ドロップで並び替え)</span></h3>
                <?php if (!empty($categories)): ?>
                    <button type="submit" class="button button-secondary" style="color: var(--wp-danger); border-color: var(--wp-border); background: #fff; padding: 4px 12px; font-size: 12px;">
                        選択した項目を削除
                    </button>
                <?php endif; ?>
            </div>
            <table class="wp-list-table" style="margin-top: 0; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center; padding: 10px;">
                            <input type="checkbox" id="select_all_cats" style="width: auto; transform: scale(1.1); cursor: pointer; margin: 0;">
                        </th>
                        <th>名前</th>
                        <th>スラッグ</th>
                        <th style="width: 140px; text-align: center;">操作</th>
                    </tr>
                </thead>
                <tbody id="category-list-body">
                    <?php if (empty($sorted_categories)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px; color: #646970;">カテゴリーが登録されていません。</td></tr>
                    <?php else: foreach ($sorted_categories as $cat): ?>
                        <tr draggable="true" 
                            data-id="<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>" 
                            data-parent-id="<?php echo htmlspecialchars($cat['parent_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                            data-depth="<?php echo $cat['depth']; ?>"
                            class="cat-row" 
                            style="cursor: move;">
                            <td style="text-align: center; padding: 10px;">
                                <input type="checkbox" name="selected_categories[]" value="<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>" class="cat-checkbox" style="width: auto; transform: scale(1.1); cursor: pointer; margin: 0;">
                            </td>
                            <td style="padding-left: <?php echo ($cat['depth'] * 20) + 10; ?>px;">
                                <strong style="font-size: 14px; color: #1d2327;">
                                    <?php echo $cat['depth'] > 0 ? '— ' : ''; ?><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </strong>
                            </td>
                            <td><code><?php echo htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <button type="button" onclick="openEditModal('<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($cat['parent_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')" class="button-link" style="font-size: 12px; margin-right: 15px; color: #2271b1; cursor: pointer; border: none; background: none; padding: 0;">編集</button>
                                <button type="button" onclick="deleteSingleCategory('<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>')" class="button-link-delete" style="font-size: 12px; cursor: pointer;">削除</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

<div id="edit-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:25px; border-radius:8px; width:400px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;">カテゴリー編集</h3>
        <form action="categories.php" method="POST">
            <input type="hidden" name="action" value="edit_category">
            <input type="hidden" name="id" id="edit_id">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">名前:</label>
                <input type="text" name="name" id="edit_name" required style="width:100%; box-sizing:border-box; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">親カテゴリー:</label>
                <select name="parent_id" id="edit_parent_id" style="width:100%; box-sizing:border-box; padding: 8px;">
                    <option value="">なし（親カテゴリー）</option>
                    <?php foreach ($sorted_categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo str_repeat('&nbsp;&nbsp;', $cat['depth']) . htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">スラッグ:</label>
                <input type="text" name="slug" id="edit_slug" style="width:100%; box-sizing:border-box; padding: 8px;">
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="document.getElementById('edit-modal').style.display='none'" class="button" style="margin-right: 10px;">キャンセル</button>
                <button type="submit" class="button button-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<form id="single_delete_form" action="categories.php" method="POST">
    <input type="hidden" name="action" value="delete_category">
    <input type="hidden" name="id" id="delete_target_id">
</form>

<script>
    function openEditModal(id, name, slug, parentId) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_slug').value = slug;
        document.getElementById('edit_parent_id').value = parentId;
        document.getElementById('edit-modal').style.display = 'flex';
    }

    let dragSrcEl = null;

    // 「ドラッグされている要素」と「その子要素」のブロックを取得する関数
    function getBranch(el) {
        const branch = [el];
        const rows = Array.from(document.querySelectorAll('.cat-row'));
        const startIndex = rows.indexOf(el);
        const parentId = el.getAttribute('data-id');
        
        for (let i = startIndex + 1; i < rows.length; i++) {
            // 現在の行の親IDが、ドラッグ対象のID（またはその子孫）であれば取り込む
            // ※簡易実装：親の次に続く「自分より階層が深いもの」は全て子とみなす
            const rowDepth = parseInt(rows[i].getAttribute('data-depth'));
            const sourceDepth = parseInt(el.getAttribute('data-depth'));
            
            if (rowDepth > sourceDepth) {
                branch.push(rows[i]);
            } else {
                break; // 階層が同じになったらそこは別カテゴリなので停止
            }
        }
        return branch;
    }

    document.querySelectorAll('.cat-row').forEach(row => {
        row.addEventListener('dragstart', function(e) {
            dragSrcEl = this;
            e.dataTransfer.effectAllowed = 'move';
        });
        row.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        row.addEventListener('drop', function(e) {
            e.preventDefault();
            if (dragSrcEl !== this) {
                const branch = getBranch(dragSrcEl);
                const tbody = document.getElementById('category-list-body');
                
                // 親の後に挿入するか、親の前に挿入するか
                // 今回は「ドラッグ先に挿入」する単純な形にしていますが、
                // branchごと移動することで親子関係を維持します
                if (Array.from(tbody.querySelectorAll('.cat-row')).indexOf(dragSrcEl) < Array.from(tbody.querySelectorAll('.cat-row')).indexOf(this)) {
                    branch.forEach(node => tbody.insertBefore(node, this.nextSibling));
                } else {
                    branch.forEach(node => tbody.insertBefore(node, this));
                }
                
                saveNewOrder();
            }
        });
    });

    function saveNewOrder() {
        const rows = document.querySelectorAll('.cat-row');
        const orderData = [];
        rows.forEach(row => {
            orderData.push({
                id: row.getAttribute('data-id'),
                parent_id: row.getAttribute('data-parent-id') 
            });
        });

        const formData = new FormData();
        formData.append('action', 'update_order');
        formData.append('order_data', JSON.stringify(orderData));

        fetch('categories.php', {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                console.log('Order saved.');
            }
        });
    }

    const selectAllCheckbox = document.getElementById('select_all_cats');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.cat-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }

    function deleteSingleCategory(id) {
        if (confirm('このカテゴリーを完全に削除しますか？')) {
            document.getElementById('delete_target_id').value = id;
            document.getElementById('single_delete_form').submit();
        }
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>