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
    $categories = json_decode(file_get_contents($categories_file), true);
    if (!is_array($categories)) {
        $categories = [];
    }
}

// メッセージ初期化
$message = '';
$error = '';

// --------------------------------------------------------------------------
// POST処理：カテゴリーの追加 / 単一削除 / 一括削除
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add_category') {
        // 新規追加
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        
        if ($name === '') {
            $error = '名前を入力してください。';
        } else {
            // スラッグが空の場合は名前を加工するか一意のIDをベースにする
            if ($slug === '') {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $name));
                if ($slug === '') { $slug = 'cat-' . time(); }
            }
            
            // スラッグの重複チェック
            $exists = false;
            foreach ($categories as $cat) {
                if ($cat['slug'] === $slug) {
                    $exists = true;
                    break;
                }
            }
            
            if ($exists) {
                $error = 'このスラッグは既に他で使用されています。';
            } else {
                $new_id = uniqid('cat_', true);
                $categories[] = [
                    'id' => $new_id,
                    'name' => $name,
                    'slug' => $slug
                ];
                file_put_contents($categories_file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $message = 'カテゴリーを追加しました。';
            }
        }
        
    } elseif ($_POST['action'] === 'delete_category') {
        // 単一削除
        $target_id = $_POST['id'] ?? '';
        $filtered = [];
        $found = false;
        
        foreach ($categories as $cat) {
            if ($cat['id'] === $target_id) {
                $found = true;
                continue; // 削除対象は含めない
            }
            $filtered[] = $cat;
        }
        
        if ($found) {
            $categories = $filtered;
            file_put_contents($categories_file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $message = 'カテゴリーを削除しました。';
        } else {
            $error = '指定されたカテゴリーが見つかりません。';
        }
        
    } elseif ($_POST['action'] === 'bulk_delete_categories') {
        // 複数一括削除
        $selected_ids = $_POST['selected_categories'] ?? [];
        if (!empty($selected_ids) && is_array($selected_ids)) {
            $filtered = [];
            $deleted_count = 0;
            
            foreach ($categories as $cat) {
                if (in_array($cat['id'], $selected_ids, true)) {
                    $deleted_count++;
                    continue;
                }
                $filtered[] = $cat;
            }
            
            $categories = $filtered;
            file_put_contents($categories_file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $message = $deleted_count . ' 件のカテゴリーを削除しました。';
        } else {
            $error = '削除するカテゴリーが選択されていません。';
        }
    }
}

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
                <p style="color: #646970; font-size: 11px; margin-top: 4px;">サイト上に表示される名前です。</p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="tag-slug" style="display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327;">スラッグ</label>
                <input type="text" name="slug" id="tag-slug" placeholder="例: news" style="width: 100%;">
                <p style="color: #646970; font-size: 11px; margin-top: 4px;">URLに適した半角小文字の英数字・ハイフンのみを入力してください。</p>
            </div>
            
            <button type="submit" class="button button-primary" style="width: 100%; padding: 10px 0;">新規カテゴリーを追加</button>
        </form>
    </div>
    
    <div style="flex: 2;" class="settings-card">
        <form action="categories.php" method="POST" id="bulk_delete_form" onsubmit="return confirm('選択したすべてのカテゴリーを削除しますか？');">
            <input type="hidden" name="action" value="bulk_delete_categories">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0;">登録済みカテゴリー</h3>
                
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
                        <th style="width: 80px; text-align: center;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 30px; color: #646970;">カテゴリーが登録されていません。</td>
                        </tr>
                    <?php else: foreach ($categories as $cat): ?>
                        <tr>
                            <td style="text-align: center; padding: 10px;">
                                <input type="checkbox" name="selected_categories[]" value="<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>" class="cat-checkbox" style="width: auto; transform: scale(1.1); cursor: pointer; margin: 0;">
                            </td>
                            <td><strong style="font-size: 14px; color: #1d2327;"><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td style="text-align: center;">
                                <button type="button" onclick="deleteSingleCategory('<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>')" class="button-link-delete" style="font-size: 12px;">削除</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </form>
    </div>

</div>

<form id="single_delete_form" action="categories.php" method="POST">
    <input type="hidden" name="action" value="delete_category">
    <input type="hidden" name="id" id="delete_target_id">
</form>

<script>
    // --------------------------------------------------------------------------
    // 全選択 / 全解除コントロールロジック
    // --------------------------------------------------------------------------
    const selectAllCheckbox = document.getElementById('select_all_cats');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.cat-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }

    // --------------------------------------------------------------------------
    // 単一カテゴリー削除スクリプト
    // --------------------------------------------------------------------------
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