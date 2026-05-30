<?php
// setup.php
define('CONFIG_FILE', __DIR__ . '/data/site_config.json');

// 二重インストール防止
if (file_exists(CONFIG_FILE)) {
    die('既にセットアップ済みです。');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // データ保存処理
    $config = [
        'site_name' => $_POST['site_name'],
        'username'  => $_POST['username'],
        'password'  => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'installed_at' => date('Y-m-d H:i:s')
    ];
    
    // ディレクトリが存在しない場合の念の為のチェック
    if (!is_dir(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0755, true);
    }
    
    file_put_contents(CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
    
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raptio 初期セットアップ</title>
    <link rel="stylesheet" href="./admin/css/admin_style.css">
    <link rel="icon" href="./admin/img/Raptio_icon.png" type="image/png">
    <style>
        /* セットアップ画面専用の微調整 */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f0f3f5; /* 管理画面の背景色 */
        }
        .setup-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .setup-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .setup-logo img {
            height: 50px;
            width: auto;
        }
        .setup-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .setup-card h1 {
            font-size: 20px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: center;
            color: #1d2327;
        }
        .input-group {
            margin-bottom: 20px;
        }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #3c434a;
        }
        .setup-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #646970;
        }
        .button-primary {
            width: 100%;
            padding: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="setup-container">
    <div class="setup-logo">
        <img src="./admin/img/logo1.png" alt="Raptio">
    </div>

    <div class="setup-card">
        <h1>初期セットアップ</h1>
        
        <form method="post">
            <div class="input-group">
                <label for="site_name">サイト名</label>
                <input type="text" id="site_name" name="site_name" placeholder="例: My Awesome Blog" required autofocus>
            </div>

            <div class="input-group">
                <label for="username">管理者ユーザー名</label>
                <input type="text" id="username" name="username" placeholder="例: admin" required>
            </div>

            <div class="input-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" placeholder="強力なパスワードを入力" required>
            </div>

            <button type="submit" class="button button-primary">設定を保存して開始</button>
        </form>
    </div>

    <div class="setup-footer">
        &copy; <?php echo date('Y'); ?> Raptio CMS
    </div>
</div>

</body>
</html>