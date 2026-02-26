<?php
require_once 'config.php';
session_start();

if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $hash = ADMIN_PASSWORD;

    $success = false;
    if (strpos($hash, '$2y$') === 0) {
        $success = password_verify($password, $hash);
    } else {
        $success = ($password === $hash);
    }

    if ($success) {
        $_SESSION['is_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'パスワードが正しくありません。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - qlo.jp</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
        }

        .login-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .error {
            color: #e74c3c;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="login-box">
        <h1 class="login-title">Admin Login</h1>
        <?php if ($error): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required autofocus>
            </div>
            <button type="submit" class="btn" style="width: 100%;">Login</button>
        </form>
        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="font-size: 0.9rem; color: #666;">&larr; サイトへ戻る</a>
        </div>
    </div>

</body>

</html>