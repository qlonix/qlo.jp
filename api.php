<?php
require_once 'config.php';
// API呼び出しはセッション認証が必須
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// multipart/form-data で 'data' フィールドにJSON文字列が入ってくる前提
$data = null;
if (isset($_POST['data'])) {
    $data = json_decode($_POST['data'], true);
} else {
    // 互換性: JSONがそのままPOSTされた場合
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
}

if ($data === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

$base_dir = __DIR__;
$uploads_dir = $base_dir . '/data/uploads';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// ファイルアップロードの処理
if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['avatar_file']['tmp_name'];
    $name = basename($_FILES['avatar_file']['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    // 拡張子チェック
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed_exts)) {
        $filename = 'avatar_' . time() . '.' . $ext;
        $destination = $uploads_dir . '/' . $filename;
        if (move_uploaded_file($tmp_name, $destination)) {
            $data['profile']['avatar_url'] = 'data/uploads/' . $filename;

            // 履歴管理（最新5件を残す）
            $files = glob($uploads_dir . '/avatar_*.*');
            if (count($files) > 5) {
                // 更新日時でソート (新しい順)
                usort($files, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });

                $keep = array_slice($files, 0, 5);
                foreach ($files as $f) {
                    if (!in_array($f, $keep)) {
                        // 現在使用中のファイルは削除しない
                        if (strpos($data['profile']['avatar_url'], basename($f)) === false) {
                            unlink($f);
                        }
                    }
                }
            }
        }
    }
}


// データ保存用ディレクトリの存在確認
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// 既存データの読み込み（Faviconなどの保持用）
$old_data = [];
if (file_exists(DATA_FILE)) {
    $old_data = json_decode(file_get_contents(DATA_FILE), true);
}

// 念のためのバックアップ
if (file_exists(DATA_FILE)) {
    copy(DATA_FILE, DATA_FILE . '.bak');
}

// タイトルが空、またはFaviconがないリンクに対して自動取得
foreach ($data['links'] as &$link) {
    // 既存データからFaviconを引き継ぐ（URLが同じ場合）
    if (isset($old_data['links'])) {
        foreach ($old_data['links'] as $old_link) {
            if ($old_link['url'] === $link['url'] && !empty($old_link['favicon_url'])) {
                $link['favicon_url'] = $old_link['favicon_url'];
                break;
            }
        }
    }

    if (!empty($link['url']) && (empty(trim($link['title'])) || empty($link['favicon_url']))) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36\r\n",
                'follow_location' => 1,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $html = @file_get_contents($link['url'], false, $context);
        if ($html) {
            // --- タイトル取得 ---
            if (empty(trim($link['title']))) {
                if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
                    $title = trim($matches[1]);
                    $encoding = mb_detect_encoding($title, ['UTF-8', 'SJIS', 'EUC-JP', 'ASCII'], true);
                    if ($encoding && $encoding !== 'UTF-8') {
                        $title = mb_convert_encoding($title, 'UTF-8', $encoding);
                    }
                    $link['title'] = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                }
            }

            // --- Favicon取得（厳密モード） ---
            if (empty($link['favicon_url'])) {
                $favicon = '';
                // 優先度の高い順に検索
                $patterns = [
                    '/<link[^>]+rel=["\'](?:apple-touch-icon(?:-precomposed)?|icon|shortcut icon)["\'][^>]+href=["\'](.*?)["\']/i',
                    '/<link[^>]+href=["\'](.*?)["\'][^>]+rel=["\'](?:apple-touch-icon(?:-precomposed)?|icon|shortcut icon)["\']/i'
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $html, $matches)) {
                        $favicon = $matches[1];
                        break;
                    }
                }

                if ($favicon) {
                    // 相対パスを絶対パスに変換
                    if (strpos($favicon, 'http') !== 0) {
                        $parsed_url = parse_url($link['url']);
                        $base = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                        if (strpos($favicon, '//') === 0) {
                            $favicon = $parsed_url['scheme'] . ':' . $favicon;
                        } elseif (strpos($favicon, '/') === 0) {
                            $favicon = $base . $favicon;
                        } else {
                            $path = dirname($parsed_url['path'] ?? '');
                            $favicon = $base . ($path === '/' ? '' : $path) . '/' . $favicon;
                        }
                    }
                    $link['favicon_url'] = $favicon;
                }
            }
        }

        // タイトルが空ならホスト名をフォールバック
        if (empty($link['title'])) {
            $link['title'] = parse_url($link['url'], PHP_URL_HOST);
        }
    }
}

// パスワード変更
if (isset($_POST['new_password']) && !empty(trim($_POST['new_password']))) {
    $new_hash = password_hash(trim($_POST['new_password']), PASSWORD_DEFAULT);
    $config_path = __DIR__ . '/config.php';
    $config_content = file_get_contents($config_path);
    // ADMIN_PASSWORD の定義箇所を置換
    // $2y$... の $ が preg_replace でバックリファレンスとして解釈されるのを防ぐためエスケープ
    $replacement = "define('ADMIN_PASSWORD', '" . addslashes($new_hash) . "');";
    $replacement = str_replace('$', '\$', $replacement);
    $config_content = preg_replace(
        "/define\('ADMIN_PASSWORD',\s*'.*?'\);/",
        $replacement,
        $config_content
    );
    file_put_contents($config_path, $config_content);
}

// JSONファイルに書き込み
$result = file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Content-Type: application/json');
if ($result !== false) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to write to file']);
}
