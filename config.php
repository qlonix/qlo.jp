<?php
define('DATA_FILE', __DIR__ . '/data/content.json');

// 管理画面へのログインパスワード (hashed)
define('ADMIN_PASSWORD', '$2y$10$kJBg8JrIhTYSqa0Vz1t7yO7j49UqKmzFhqlsGz4/AHyefngqMnhPq');

function check_login()
{
    session_start();
    if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}
