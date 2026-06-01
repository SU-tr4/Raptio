<?php
// admin/site-sidebar.php
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }

$config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
$widgets = $config_data['widgets'] ?? ['sidebar' => [], 'footer1' => [], 'footer2' => []];

$page_title = 'ウィジェット管理';
$current_page = 'sidebar';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

function getWidgetDefinition($type) {
    $defs = [
        'recent' => ['label' => '新着記事', 'desc' => '最近の投稿をリスト表示', 'fields' => ['title' => 'テキスト', 'count' => 'number']],
        'popular' => ['label' => '人気記事', 'desc' => '閲覧数の多い記事を表示', 'fields' => ['title' => 'テキスト', 'count' => 'number']],
        'text' => ['label' => 'テキスト', 'desc' => '任意のテキストを表示', 'fields' => ['title' => 'テキスト', 'content' => 'textarea']],
        'html' => ['label' => 'HTML', 'desc' => 'カスタムHTMLコード', 'fields' => ['html_code' => 'textarea']],
        'categories' => ['label' => 'カテゴリー', 'desc' => '記事のカテゴリー一覧', 'fields' => ['title' => 'テキスト']],
        'search' => ['label' => '検索', 'desc' => 'サイト内検索フォーム', 'fields' => ['title' => 'テキスト']]
    ];
    return $defs[$type] ?? ['label' => '不明', 'desc' => '', 'fields' => []];
}

function renderWidgetCard($areaId, $index, $type, $settings = []) {
    $def = getWidgetDefinition($type);
    ob_start();
?>
    <div class="widget-card" draggable="true" ondragstart="draggedItem=this; event.stopPropagation();" data-type="<?php echo $type; ?>">
        <div class="widget-card-header" onclick="toggleWidget(this)">
            <strong><?php echo $def['label']; ?></strong>
            <button type="button" class="button-link-delete" onclick="event.stopPropagation(); this.closest('.widget-card').remove()">削除</button>
        </div>
        <div class="widget-card-body">
            <input type="hidden" name="widgets[<?php echo $areaId; ?>][<?php echo $index; ?>][type]" value="<?php echo $type; ?>">
            <?php foreach ($def['fields'] as $key => $fieldType): ?>
                <div class="form-group">
                    <label><?php echo ucfirst($key); ?></label>
                    <?php if ($fieldType === 'textarea'): ?>
                        <textarea name="widgets[<?php echo $areaId; ?>][<?php echo $index; ?>][<?php echo $key; ?>]"><?php echo htmlspecialchars($settings[$key] ?? ''); ?></textarea>
                    <?php elseif ($fieldType === 'number'): ?>
                        <input type="number" name="widgets[<?php echo $areaId; ?>][<?php echo $index; ?>][<?php echo $key; ?>]" value="<?php echo htmlspecialchars($settings[$key] ?? '5'); ?>">
                    <?php else: ?>
                        <input type="text" name="widgets[<?php echo $areaId; ?>][<?php echo $index; ?>][<?php echo $key; ?>]" value="<?php echo htmlspecialchars($settings[$key] ?? ''); ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}
?>

<link rel="stylesheet" href="css/admin_widgets.css">

<div class="wp-admin-header"><h2>ウィジェット管理</h2></div>

<form id="widgetSettingsForm" method="POST">
    <div class="widget-manager-layout">
        <div class="widget-catalog">
            <h3>利用可能なウィジェット</h3>
            <div class="catalog-grid">
                <?php foreach (['recent', 'popular', 'text', 'html', 'categories', 'search'] as $type): $def = getWidgetDefinition($type); ?>
                    <div class="catalog-item" draggable="true" ondragstart="draggedItem=this; event.stopPropagation();" data-type="<?php echo $type; ?>">
                        <?php echo $def['label']; ?>
                        <span class="catalog-item-desc"><?php echo $def['desc']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="widget-areas">
            <?php foreach (['sidebar' => 'サイドバー', 'footer1' => 'フッター１', 'footer2' => 'フッター２'] as $id => $title): ?>
                <div class="widget-postbox <?php echo empty($widgets[$id]) ? 'is-closed' : ''; ?>" id="widget-postbox-<?php echo $id; ?>">
                    <div class="widget-postbox-header" onclick="togglePostbox('<?php echo $id; ?>')"><?php echo $title; ?></div>
                    <div class="drop-zone" id="zone-<?php echo $id; ?>" data-area="<?php echo $id; ?>" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                        <?php 
                        if (isset($widgets[$id]) && is_array($widgets[$id])) {
                            foreach ($widgets[$id] as $index => $w) {
                                if (!isset($w['type'])) continue;
                                $type = $w['type'];
                                $settings = $w;
                                unset($settings['type']);
                                echo renderWidgetCard($id, $index, $type, $settings);
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccd0d4;">
        <button type="submit" class="button button-primary">変更を保存</button>
    </div>
</form>

<script>
let draggedItem = null;

function renumberWidgets() {
    document.querySelectorAll('.drop-zone').forEach(zone => {
        const areaId = zone.dataset.area;
        const widgets = zone.querySelectorAll('.widget-card');
        widgets.forEach((widget, index) => {
            widget.querySelectorAll('input, textarea').forEach(input => {
                let name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/widgets\[[^\]]+\]\[\d+\]/, `widgets[${areaId}][${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
    });
}

document.getElementById('widgetSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    renumberWidgets();
    
    const formData = new FormData(this);
    formData.append('action', 'save_widgets');
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('保存しました');
        } else {
            alert('保存エラー: ' + (data.error || '不明なエラー'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('保存中にエラーが発生しました');
    });
});

function handleDragOver(e) { 
    e.preventDefault();
    const zone = e.currentTarget;
    zone.classList.add('drag-over');
}

function handleDragLeave(e) {
    const zone = e.currentTarget;
    if (!zone.contains(e.relatedTarget)) {
        zone.classList.remove('drag-over');
    }
}

function handleDrop(e) {
    e.preventDefault();
    const zone = e.currentTarget;
    zone.classList.remove('drag-over');
    
    const postbox = zone.closest('.widget-postbox');
    postbox.classList.remove('is-closed');
    zone.querySelectorAll('.widget-card-body').forEach(body => body.classList.remove('open'));

    const areaId = zone.dataset.area;
    let targetCard;

    if (draggedItem && draggedItem.classList.contains('catalog-item')) {
        const type = draggedItem.dataset.type;
        const html = createWidgetTemplate(areaId, type);
        zone.insertAdjacentHTML('beforeend', html);
        targetCard = zone.lastElementChild;
    } else if (draggedItem) {
        zone.appendChild(draggedItem);
        targetCard = draggedItem;
    }

    if (targetCard) {
        targetCard.querySelector('.widget-card-body').classList.add('open');
    }
    draggedItem = null;
}

function togglePostbox(areaId) {
    const postbox = document.getElementById('widget-postbox-' + areaId);
    postbox.classList.toggle('is-closed');
}

function toggleWidget(header) {
    const body = header.nextElementSibling;
    const zone = header.closest('.drop-zone');
    
    zone.querySelectorAll('.widget-card-body').forEach(el => {
        if (el !== body) el.classList.remove('open');
    });
    
    body.classList.toggle('open');
}

function createWidgetTemplate(areaId, type) {
    const labels = { 'recent': '新着記事', 'popular': '人気記事', 'text': 'テキスト', 'html': 'HTML', 'categories': 'カテゴリー', 'search': '検索' };
    let html = `
        <div class="widget-card" draggable="true" ondragstart="draggedItem=this; event.stopPropagation();" data-type="${type}">
            <div class="widget-card-header" onclick="toggleWidget(this)">
                <strong>${labels[type]}</strong>
                <button type="button" class="button-link-delete" onclick="event.stopPropagation(); this.closest('.widget-card').remove()">削除</button>
            </div>
            <div class="widget-card-body">
                <input type="hidden" name="widgets[${areaId}][0][type]" value="${type}">
    `;
    
    if (type === 'recent' || type === 'popular') {
        html += `<div class="form-group"><label>Title</label><input type="text" name="widgets[${areaId}][0][title]"></div>`;
        html += `<div class="form-group"><label>Count</label><input type="number" name="widgets[${areaId}][0][count]" value="5"></div>`;
    } else if (type === 'text') {
        html += `<div class="form-group"><label>Title</label><input type="text" name="widgets[${areaId}][0][title]"></div>`;
        html += `<div class="form-group"><label>Content</label><textarea name="widgets[${areaId}][0][content]"></textarea></div>`;
    } else if (type === 'html') {
        html += `<div class="form-group"><label>Html_code</label><textarea name="widgets[${areaId}][0][html_code]"></textarea></div>`;
    } else {
        html += `<div class="form-group"><label>Title</label><input type="text" name="widgets[${areaId}][0][title]"></div>`;
    }

    html += `</div></div>`;
    return html;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>