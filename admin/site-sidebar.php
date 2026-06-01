<?php
// admin/site-sidebar.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/widget-registry.php'; 

if (!check_raptio_auth()) { header('Location: index.php'); exit; }

$config_data = file_exists(CONFIG_FILE) ? json_decode(file_get_contents(CONFIG_FILE), true) : [];
$widgets = $config_data['widgets'] ?? ['sidebar' => [], 'footer1' => [], 'footer2' => []];

$page_title = 'ウィジェット管理';
$current_page = 'sidebar';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// ウィジェットカード生成関数
function renderWidgetCard($areaId, $index, $type, $settings = []) {
    $def = get_widget_definition($type);
    ob_start();
?>
    <div class="widget-card" draggable="true" ondragstart="draggedItem=this; event.stopPropagation();" data-type="<?php echo $type; ?>">
        <div class="widget-card-header" onclick="toggleWidget(this)">
            <strong><?php echo htmlspecialchars($def['label'] ?? '不明'); ?></strong>
            <button type="button" class="button-link-delete" onclick="event.stopPropagation(); this.closest('.widget-card').remove()">削除</button>
        </div>
        <div class="widget-card-body">
            <input type="hidden" name="widgets[<?php echo $areaId; ?>][<?php echo $index; ?>][type]" value="<?php echo $type; ?>">
            <?php foreach ($def['fields'] as $key => $field): ?>
                <div class="form-group">
                    <label><?php echo htmlspecialchars($field['label']); ?></label>
                    <?php if ($field['type'] === 'textarea'): ?>
                        <textarea name="widgets[<?php echo $areaId; ?>][<?php echo $index; ?>][<?php echo $key; ?>]"><?php echo htmlspecialchars($settings[$key] ?? ''); ?></textarea>
                    <?php elseif ($field['type'] === 'number'): ?>
                        <input type="number" name="widgets[<?php echo $areaId; ?>][<?php echo $index; ?>][<?php echo $key; ?>]" value="<?php echo htmlspecialchars($settings[$key] ?? '5'); ?>">
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select name="widgets[<?php echo $areaId; ?>][<?php echo $index; ?>][<?php echo $key; ?>]">
                            <?php foreach ($field['options'] as $val => $label): ?>
                                <option value="<?php echo $val; ?>" <?php echo ($settings[$key] ?? '') === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
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
                <?php foreach (get_all_widget_definitions() as $type => $def): ?>
                    <div class="catalog-item" draggable="true" ondragstart="draggedItem=this; event.stopPropagation();" data-type="<?php echo $type; ?>">
                        <?php echo htmlspecialchars($def['label']); ?>
                        <span class="catalog-item-desc"><?php echo htmlspecialchars($def['desc']); ?></span>
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
const widgetDefinitions = <?php echo json_encode(get_all_widget_definitions()); ?>;
let draggedItem = null;

function renumberWidgets() {
    document.querySelectorAll('.drop-zone').forEach(zone => {
        const areaId = zone.dataset.area;
        const widgets = zone.querySelectorAll('.widget-card');
        widgets.forEach((widget, index) => {
            widget.querySelectorAll('input, textarea, select').forEach(input => {
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

function handleDragOver(e) { e.preventDefault(); e.currentTarget.classList.add('drag-over'); }
function handleDragLeave(e) { if (!e.currentTarget.contains(e.relatedTarget)) e.currentTarget.classList.remove('drag-over'); }

function handleDrop(e) {
    e.preventDefault();
    const zone = e.currentTarget;
    zone.classList.remove('drag-over');
    const postbox = zone.closest('.widget-postbox');
    postbox.classList.remove('is-closed');

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
    draggedItem = null;
}

function togglePostbox(areaId) { document.getElementById('widget-postbox-' + areaId).classList.toggle('is-closed'); }
function toggleWidget(header) {
    const body = header.nextElementSibling;
    const zone = header.closest('.drop-zone');
    zone.querySelectorAll('.widget-card-body').forEach(el => { if (el !== body) el.classList.remove('open'); });
    body.classList.toggle('open');
}

function createWidgetTemplate(areaId, type) {
    const def = widgetDefinitions[type];
    if (!def) return '';

    let html = `
        <div class="widget-card" draggable="true" ondragstart="draggedItem=this; event.stopPropagation();" data-type="${type}">
            <div class="widget-card-header" onclick="toggleWidget(this)">
                <strong>${def.label}</strong>
                <button type="button" class="button-link-delete" onclick="event.stopPropagation(); this.closest('.widget-card').remove()">削除</button>
            </div>
            <div class="widget-card-body">
                <input type="hidden" name="widgets[${areaId}][0][type]" value="${type}">
    `;
    
    for (const [key, field] of Object.entries(def.fields)) {
        html += `<div class="form-group"><label>${field.label}</label>`;
        
        if (field.type === 'textarea') {
            html += `<textarea name="widgets[${areaId}][0][${key}]"></textarea>`;
        } else if (field.type === 'number') {
            html += `<input type="number" name="widgets[${areaId}][0][${key}]" value="5">`;
        } else if (field.type === 'select') {
            html += `<select name="widgets[${areaId}][0][${key}]">`;
            for (const [val, label] of Object.entries(field.options)) {
                html += `<option value="${val}">${label}</option>`;
            }
            html += `</select>`;
        } else {
            html += `<input type="text" name="widgets[${areaId}][0][${key}]">`;
        }
        html += `</div>`;
    }
    html += `</div></div>`;
    return html;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>