<?php
/**
 * fix_filepaths.php
 * 既存の posts_index.json / pages_index.json / posts_*_index.json の
 * file_path をWindowsパスから相対パスに一括修正するスクリプト。
 *
 * 使い方: raptio/ ディレクトリに置いて
 *   http://localhost/raptio/fix_filepaths.php
 * にアクセス。完了したら削除すること。
 */

$data_dir = __DIR__ . '/data';

// 対象ファイルを収集
$index_files = [];
foreach (glob($data_dir . '/*_index.json') as $f) {
    $index_files[] = $f;
}
// posts_index.json / pages_index.json も明示的に含める
foreach ([$data_dir . '/posts_index.json', $data_dir . '/pages_index.json'] as $f) {
    if (file_exists($f) && !in_array($f, $index_files)) {
        $index_files[] = $f;
    }
}

$results = [];

foreach ($index_files as $index_file) {
    $data = json_decode(file_get_contents($index_file), true);
    if (!is_array($data)) continue;

    $changed = 0;
    foreach ($data as &$item) {
        $raw = $item['file_path'] ?? '';
        if ($raw === '') continue;

        $normalized = str_replace('\\', '/', $raw);

        // Windowsパス or Linuxの絶対パス → data/ 以降を抽出して相対パスに
        if (preg_match('/^[A-Za-z]:\//', $normalized) || str_starts_with($normalized, '/')) {
            $pos = strpos($normalized, '/data/');
            if ($pos !== false) {
                $relative = ltrim(substr($normalized, $pos), '/');
                $item['file_path'] = $relative;
                $changed++;
            }
        }
    }
    unset($item);

    if ($changed > 0) {
        file_put_contents($index_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    $results[] = basename($index_file) . ": {$changed}件修正";
}

echo "<pre>修正完了:\n" . implode("\n", $results) . "\n\n※このファイル (fix_filepaths.php) を削除してください。</pre>";