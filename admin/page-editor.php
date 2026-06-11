<?php
// admin/page-editor.php
// editor.php に統合されたため、既存リンクが壊れないようリダイレクトする

$id = $_GET['id'] ?? '';
$target = 'editor.php?mode=page' . ($id !== '' ? '&id=' . urlencode($id) : '');
header('Location: ' . $target);
exit;