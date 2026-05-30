<?php
// admin/cleanup.php
require_once __DIR__ . '/auth.php';
if (!check_raptio_auth()) { header('Location: index.php'); exit; }

// ファイル名をサイトの実際の構成に合わせて修正しました
$config_file = 'C:/xampp/htdocs/raptio/data/site_config.json';

if (file_exists($config_file)) {
    $data = json_decode(file_get_contents($config_file), true);
    
    // 不要な古いキーを削除
    if (isset($data['header_menu'])) {
        unset($data['header_menu']);
        
        if (file_put_contents($config_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false) {
            echo "<h1>成功: データがクリーンアップされました</h1>";
            echo "<p>不要な 'header_menu' 項目を削除しました。</p>";
        } else {
            echo "<h1>エラー: 書き込みに失敗しました</h1>";
        }
    } else {
        echo "<h1>完了: すでにクリーンな状態です</h1>";
    }
} else {
    echo "<h1>エラー: ファイルが見つかりません</h1>";
    echo "<p>パスを確認してください: " . htmlspecialchars($config_file) . "</p>";
}
?>