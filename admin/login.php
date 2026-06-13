<?php
// admin/login.php
// ログイン画面の表示用テンプレート
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raptio CMS &rsaquo; ログイン</title>
    <link rel="icon" href="./img/Raptio_icon.png" type="image/png">
    <link rel="stylesheet" href="./css/admin_style.css">
    <style>
        body.wp-login-body {
            background-color: #f0f3f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-box {
            width: 100%;
            max-width: 350px;
            padding: 20px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-logo img {
            height: 50px;
            width: auto;
        }
        .login-card {
            background: #fff;
            padding: 30px;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .error-message {
            background: #fdf7f7;
            border-left: 4px solid #d63638;
            padding: 12px;
            margin-bottom: 20px;
            color: #d63638;
            font-size: 13px;
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
        .button-primary {
            width: 100%;
            padding: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body class="wp-login-body">
    <div class="login-box">
        <div class="login-logo">
            <img src="./img/logo1.png" alt="Raptio">
        </div>
        
        <div class="login-card">
            <?php if (isset($_GET['error']) && $_GET['error'] === '1'): ?>
                <div class="error-message">
                    ユーザー名またはパスワードが正しくありません。
                </div>
            <?php endif; ?>
            
            <form action="api.php" method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="input-group">
                    <label for="username">ユーザー名</label>
                    <input type="text" name="username" id="username" required autofocus>
                </div>
                
                <div class="input-group">
                    <label for="user_pass">パスワード</label>
                    <input type="password" name="password" id="user_pass" required>
                </div>
                
                <button type="submit" class="button button-primary">ログイン</button>
            </form>
        </div>
    </div>
</body>
</html>