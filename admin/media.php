<?php
// admin/media.php
require_once __DIR__ . '/auth.php';

// 未認証時はダッシュボードへ強制リダイレクト（ログインを挟むため）
if (!check_raptio_auth()) {
    header('Location: index.php');
    exit;
}

$page_title = 'メディアライブラリ';
$current_page = 'media';
$sub_page = '';

// メディアアップロード先ディレクトリの設定
$upload_dir = __DIR__ . '/../uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 許可する拡張子
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

// メッセージ初期化
$message = '';
$error = '';

// --------------------------------------------------------------------------
// POST処理：ファイルのアップロード
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['async_upload'])) {
    $file = $_FILES['async_upload'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed_extensions, true)) {
            // ファイル名の安全化（重複回避のためのタイムスタンプ付与）
            $safe_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $file['name']);
            $dest_path = $upload_dir . $safe_filename;
            
            if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                $message = 'ファイルをアップロードしました。';
            } else {
                $error = 'ファイルの保存に失敗しました。';
            }
        } else {
            $error = '許可されていないファイル形式です（jpg, png, gif, webp, svgのみ可）。';
        }
    } else {
        $error = 'アップロード中にエラーが発生しました。';
    }
}

// --------------------------------------------------------------------------
// POST処理：ファイルの単一削除 / 複数一括削除
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_media') {
        // 単一削除
        $target_file = $_POST['filename'] ?? '';
        $target_path = realpath($upload_dir . $target_file);
        
        if ($target_path && strpos($target_path, realpath($upload_dir)) === 0 && file_exists($target_path)) {
            if (unlink($target_path)) {
                $message = 'ファイルを削除しました。';
            } else {
                $error = 'ファイルの削除に失敗しました。';
            }
        } else {
            $error = '不正なファイル操作です。';
        }
    } elseif ($_POST['action'] === 'bulk_delete_media') {
        // 複数一括削除
        $bulk_files = $_POST['selected_files'] ?? [];
        if (!empty($bulk_files) && is_array($bulk_files)) {
            $deleted_count = 0;
            $failed_count = 0;
            
            foreach ($bulk_files as $target_file) {
                $target_path = realpath($upload_dir . $target_file);
                if ($target_path && strpos($target_path, realpath($upload_dir)) === 0 && file_exists($target_path)) {
                    if (unlink($target_path)) {
                        $deleted_count++;
                    } else {
                        $failed_count++;
                    }
                }
            }
            
            if ($deleted_count > 0) {
                $message = $deleted_count . ' 件のファイルを削除しました。';
            }
            if ($failed_count > 0) {
                $error = $failed_count . ' 件のファイルの削除に失敗しました。';
            }
        } else {
            $error = '削除するファイルが選択されていません。';
        }
    }
}

// --------------------------------------------------------------------------
// アップロード済みファイル一覧の取得
// --------------------------------------------------------------------------
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
                    'url' => '../uploads/' . $file,
                    'date' => date('Y-m-d H:i', filemtime($file_path)),
                    'size' => round(filesize($file_path) / 1024, 1) . ' KB'
                ];
            }
        }
    }
    // 新しい順にソート
    usort($media_files, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="wp-content-title-area">
    <h2>メディアライブラリ</h2>
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

<div class="settings-card" style="margin-bottom: 30px;">
    <h3>新規メディアのアップロード</h3>
    <form action="media.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 15px; align-items: center; margin-top: 10px;">
        <div style="flex: 1;">
            <input type="file" name="async_upload" accept="image/*" style="border: 1px dashed var(--wp-border); padding: 15px; background: #fafafa; border-radius: 4px; width: 100%; cursor: pointer;" required>
        </div>
        <button type="submit" class="button button-primary" style="padding: 14px 24px;">アップロード</button>
    </form>
    <p class="form-description">対応フォーマット: JPG, PNG, GIF, WEBP, SVG</p>
</div>

<form action="media.php" method="POST" id="bulk_delete_form" onsubmit="return confirm('選択したすべてのファイルを完全に削除しますか？');">
    <input type="hidden" name="action" value="bulk_delete_media">

    <div class="settings-card">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f1; padding-bottom: 12px; margin-bottom: 15px;">
            <h3>アップロード済みファイル</h3>
            
            <?php if (!empty($media_files)): ?>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <label style="font-size: 13px; color: var(--wp-text-main); cursor: pointer; display: flex; align-items: center; gap: 6px; font-weight: 600;">
                        <input type="checkbox" id="select_all_media" style="width: auto; transform: scale(1.2); margin: 0; cursor: pointer;">
                        すべて選択 / 解除
                    </label>
                    <button type="submit" class="button button-secondary" style="color: var(--wp-danger); border-color: var(--wp-border); background: #fff;">
                        選択した項目を削除
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($media_files)): ?>
            <p style="text-align: center; padding: 40px 0; color: #646970; font-size: 14px;">メディアファイルが見つかりません。画像をアップロードしてみましょう。</p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 20px;">
                <?php foreach ($media_files as $item): ?>
                    <div class="media-item-card" style="background: #fff; border: 1px solid var(--wp-border); border-radius: 4px; overflow: hidden; display: flex; flex-direction: column; position: relative; transition: transform 0.1s ease;">
                        
                        <div style="position: absolute; top: 8px; left: 8px; z-index: 10; background: rgba(255,255,255,0.85); padding: 4px; border-radius: 3px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.15);">
                            <input type="checkbox" name="selected_files[]" value="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" class="media-checkbox" style="width: 16px; height: 16px; margin: 0; cursor: pointer;">
                        </div>

                        <div style="width: 100%; padding-top: 100%; position: relative; background: #eaeaea; border-bottom: 1px solid #f0f0f1; cursor: pointer;" onclick="openLightBox('<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $item['size']; ?>', '<?php echo $item['date']; ?>')">
                            <img src="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        
                        <div style="padding: 10px; font-size: 11px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div style="margin-bottom: 8px;">
                                <span style="font-weight: 600; color: #1d2327; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <span style="color: #646970; display: block; margin-top: 2px;"><?php echo $item['size']; ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f1; padding-top: 8px; margin-top: auto;">
                                <button type="button" onclick="openLightBox('<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $item['size']; ?>', '<?php echo $item['date']; ?>')" class="button button-secondary" style="padding: 2px 6px; font-size: 11px;">詳細表示</button>
                                <button type="button" onclick="deleteSingleMedia('<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>')" class="button-link-delete" style="font-size: 11px;">削除</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</form>

<form id="single_delete_form" action="media.php" method="POST">
    <input type="hidden" name="action" value="delete_media">
    <input type="hidden" name="filename" id="delete_filename">
</form>

<div id="media_lightbox" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(16, 20, 22, 0.85); z-index: 50000; justify-content: center; align-items: center; padding: 20px;" onclick="closeLightBox()">
    
    <div style="background: #fff; width: 100%; max-width: 850px; height: auto; max-height: 85vh; border-radius: 6px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; display: flex; position: relative;" onclick="event.stopPropagation()">
        
        <div style="position: absolute; top: 12px; right: 18px; color: #646970; font-size: 28px; font-weight: 300; cursor: pointer; user-select: none; z-index: 10;" onclick="closeLightBox()">&times;</div>
        
        <div style="flex: 1.2; background: #f0f3f5; display: flex; align-items: center; justify-content: center; padding: 30px; min-height: 350px;">
            <img id="lightbox_img" src="" alt="" style="max-width: 100%; max-height: 65vh; object-fit: contain; border-radius: 2px;">
        </div>
        
        <div style="flex: 0.8; padding: 25px; border-left: 1px solid #dcdcde; display: flex; flex-direction: column; background: #fafafa; justify-content: space-between; overflow-y: auto;">
            <div>
                <h3 style="font-size: 16px; font-weight: 600; color: #1d2327; margin-bottom: 18px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">添付ファイルの詳細</h3>
                
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 12px; color: var(--wp-text-main);">
                    <div>
                        <strong style="display: block; color: #1d2327; margin-bottom: 3px;">ファイル名:</strong>
                        <span id="lightbox_filename" style="word-break: break-all; font-family: monospace;"></span>
                    </div>
                    <div>
                        <strong style="display: block; color: #1d2327; margin-bottom: 3px;">ファイルサイズ:</strong>
                        <span id="lightbox_filesize"></span>
                    </div>
                    <div>
                        <strong style="display: block; color: #1d2327; margin-bottom: 3px;">アップロード日時:</strong>
                        <span id="lightbox_filedate"></span>
                    </div>
                    <div style="margin-top: 5px;">
                        <strong style="display: block; color: #1d2327; margin-bottom: 4px;">ファイルURL:</strong>
                        <input type="text" id="lightbox_fileurl" readonly style="background: #fff; font-family: monospace; font-size: 11px; padding: 6px 8px; border: 1px solid var(--wp-border); border-radius: 3px; cursor: text;" onclick="this.select()">
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
                <button type="button" id="lightbox_copy_btn" class="button button-primary" style="font-size: 12px;">URLをコピー</button>
                <button type="button" id="lightbox_delete_btn" class="button-link-delete" style="font-size: 12px;">ファイルを削除</button>
            </div>
        </div>
    </div>
</div>

<script>
    // --------------------------------------------------------------------------
    // 全選択 / 全解除コントロールロジック
    // --------------------------------------------------------------------------
    const selectAllCheckbox = document.getElementById('select_all_media');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.media-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }

    // --------------------------------------------------------------------------
    // 単一ファイル削除スクリプト
    // --------------------------------------------------------------------------
    function deleteSingleMedia(filename) {
        if (confirm('このファイルを完全に削除しますか？')) {
            document.getElementById('delete_filename').value = filename;
            document.getElementById('single_delete_form').submit();
        }
    }

    // --------------------------------------------------------------------------
    // モーダル（ライトボックス）詳細制御関数
    // --------------------------------------------------------------------------
    function openLightBox(url, name, size, date) {
        const lightbox = document.getElementById('media_lightbox');
        const img = document.getElementById('lightbox_img');
        
        // メタテキストのバインド
        document.getElementById('lightbox_filename').textContent = name;
        document.getElementById('lightbox_filesize').textContent = size;
        document.getElementById('lightbox_filedate').textContent = date;
        
        // フルURLの生成とインプットバインド
        const absoluteUrl = window.location.origin + '/' + url.replace('../', '');
        const urlInput = document.getElementById('lightbox_fileurl');
        urlInput.value = absoluteUrl;

        // ボタンの動的アクションバインド
        document.getElementById('lightbox_copy_btn').onclick = function() {
            urlInput.select();
            document.execCommand('copy');
            alert('URLをクリップボードにコピーしました！\n' + absoluteUrl);
        };
        
        document.getElementById('lightbox_delete_btn').onclick = function() {
            closeLightBox();
            deleteSingleMedia(name);
        };
        
        img.src = url;
        lightbox.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeLightBox() {
        const lightbox = document.getElementById('media_lightbox');
        lightbox.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLightBox();
        }
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>