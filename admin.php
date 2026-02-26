<?php
require_once 'config.php';
check_login();

$data = [
    'profile' => ['name' => '', 'bio' => '', 'avatar_url' => ''],
    'links' => [],
    'embeds' => ['instagram' => '']
];

if (file_exists(DATA_FILE)) {
    $json = file_get_contents(DATA_FILE);
    $parsed = json_decode($json, true);
    if ($parsed) {
        $data = array_merge($data, $parsed);
    }
}

$uploads_dir = __DIR__ . '/data/uploads';
$history_files = [];
if (is_dir($uploads_dir)) {
    $files = glob($uploads_dir . '/avatar_*.*');
    if ($files) {
        // 新しい順にソート
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        foreach ($files as $f) {
            $history_files[] = 'data/uploads/' . basename($f);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - qlo.jp</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="admin-container">
        <div class="header-flex">
            <h2>管理者ダッシュボード</h2>
            <div>
                <a href="index.php" class="btn" style="background-color: #95a5a6; margin-right: 10px;"
                    target="_blank">サイトを確認する</a>
                <a href="logout.php" class="btn btn-danger">ログアウト</a>
            </div>
        </div>

        <div class="alert" id="success-alert">保存しました👍</div>

        <form id="admin-form">
            <h3>プロフィール設定</h3>
            <div class="form-group" style="margin-top: 20px;">
                <label>サイト名 / あなたの名前</label>
                <input type="text" name="profile_name" class="form-control"
                    value="<?= htmlspecialchars($data['profile']['name']) ?>">
            </div>
            <div class="form-group">
                <label>アバター画像URL</label>
                <input type="text" name="profile_avatar" id="profile_avatar" class="form-control"
                    value="<?= htmlspecialchars($data['profile']['avatar_url']) ?>"
                    placeholder="https://example.com/avatar.jpg">
                <div style="margin-top: 10px;">
                    <label>または画像をアップロード</label>
                    <input type="file" name="avatar_file" accept="image/png, image/jpeg, image/gif, image/webp"
                        class="form-control">
                </div>

                <?php if (!empty($history_files)): ?>
                    <div style="margin-top: 15px;">
                        <label>履歴から選択して復元</label>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php foreach ($history_files as $url): ?>
                                <img src="<?= htmlspecialchars($url) ?>" tabindex="0"
                                    style="width: 50px; height: 50px; object-fit: cover; cursor: pointer; border-radius: 4px; border: <?= ($data['profile']['avatar_url'] === $url) ? '3px solid #3498db' : '1px solid #ccc' ?>;"
                                    onclick="document.getElementById('profile_avatar').value = '<?= htmlspecialchars($url) ?>'; Array.from(this.parentElement.children).forEach(img => img.style.border = '1px solid #ccc'); this.style.border = '3px solid #3498db';">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>自己紹介 (改行対応)</label>
                <textarea name="profile_bio"
                    class="form-control"><?= htmlspecialchars(trim($data['profile']['bio'])) ?></textarea>
            </div>

            <h3 style="margin-top: 40px;">外部リンク設定</h3>
            <div id="links-container" style="margin-top: 20px;">
                <?php foreach ($data['links'] as $index => $link): ?>
                    <div class="admin-link-item">
                        <div
                            style="position: absolute; top: 15px; right: 15px; display: flex; gap: 15px; font-size: 0.9rem;">
                            <span style="color: #3498db; cursor: pointer;" onclick="moveUp(this)">▲上へ</span>
                            <span style="color: #3498db; cursor: pointer;" onclick="moveDown(this)">▼下へ</span>
                            <span class="remove-link" onclick="this.closest('.admin-link-item').remove()">削除</span>
                        </div>
                        <div class="form-group">
                            <label>タイトル</label>
                            <input type="text" name="link_title[]" class="form-control"
                                value="<?= htmlspecialchars($link['title']) ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>URL</label>
                            <input type="text" name="link_url[]" class="form-control"
                                value="<?= htmlspecialchars($link['url']) ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn" id="add-link-btn"
                style="background-color: #3498db; margin-bottom: 30px;">+ リンクを追加</button>

            <h3 style="margin-top: 40px;">SNS埋め込み設定</h3>
            <div class="form-group" style="margin-top: 20px;">
                <label>Instagram ユーザーID</label>
                <input type="text" name="embed_instagram" class="form-control" placeholder="例: qlopics"
                    value="<?= htmlspecialchars($data['embeds']['instagram'] ?? '') ?>">
                <small style="color: #666;">※ InstagramのユーザーID（@を除いた名前）を入力してください。自動的にプロフィールが埋め込まれます。</small>
            </div>

            <h3 style="margin-top: 40px;">セキュリティ設定</h3>
            <div class="form-group" style="margin-top: 20px;">
                <label>新しい管理パスワード（変更する場合のみ入力）</label>
                <input type="password" name="new_password" class="form-control" placeholder="新しいパスワード">
                <small style="color: #666;">※ 変更すると次回ログイン時から新しいパスワードが必要になります。</small>
            </div>

            <hr style="margin: 40px 0; border: none; border-top: 1px solid #eee;">

            <button type="submit" class="btn" style="width: 100%; font-size: 1.1rem; padding: 15px;">変更を保存する</button>
        </form>
    </div>

    <!-- 新規リンク追加用のテンプレート（非表示） -->
    <template id="link-template">
        <div class="admin-link-item">
            <div style="position: absolute; top: 15px; right: 15px; display: flex; gap: 15px; font-size: 0.9rem;">
                <span style="color: #3498db; cursor: pointer;" onclick="moveUp(this)">▲上へ</span>
                <span style="color: #3498db; cursor: pointer;" onclick="moveDown(this)">▼下へ</span>
                <span class="remove-link" onclick="this.closest('.admin-link-item').remove()">削除</span>
            </div>
            <div class="form-group">
                <label>タイトル</label>
                <input type="text" name="link_title[]" class="form-control" placeholder="例: GitHub（空欄で自動取得）">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>URL</label>
                <input type="text" name="link_url[]" class="form-control url-input" placeholder="example.com">
            </div>
        </div>
    </template>

    <script>
        function moveUp(el) {
            const item = el.closest('.admin-link-item');
            if (item.previousElementSibling) {
                item.parentNode.insertBefore(item, item.previousElementSibling);
            }
        }
        function moveDown(el) {
            const item = el.closest('.admin-link-item');
            if (item.nextElementSibling) {
                item.parentNode.insertBefore(item.nextElementSibling, item);
            }
        }

        // URLの自動補完ロジック
        function autocompleteUrl(input) {
            let val = input.value.trim();
            if (val && !val.startsWith('http://') && !val.startsWith('https://') && !val.startsWith('/') && !val.startsWith('mailto:')) {
                input.value = 'https://' + val;
            }
        }

        // 既存と新規のURL入力フィールドにイベントを設定
        document.addEventListener('blur', function (e) {
            if (e.target && (e.target.name === 'link_url[]' || e.target.id === 'profile_avatar')) {
                autocompleteUrl(e.target);
            }
        }, true);

        document.getElementById('add-link-btn').addEventListener('click', function () {
            const template = document.getElementById('link-template');
            const clone = template.content.cloneNode(true);
            document.getElementById('links-container').appendChild(clone);
        });

        document.getElementById('admin-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            // JSON保存用のデータ構造を作成
            const saveObj = {
                profile: {
                    name: formData.get('profile_name'),
                    bio: formData.get('profile_bio'),
                    avatar_url: formData.get('profile_avatar')
                },
                links: [],
                embeds: {
                    instagram: formData.get('embed_instagram'),
                    x: formData.get('embed_x')
                }
            };

            const linkTitles = formData.getAll('link_title[]');
            const linkUrls = formData.getAll('link_url[]');

            for (let i = 0; i < linkTitles.length; i++) {
                if (linkUrls[i].trim() !== '') {
                    saveObj.links.push({
                        title: linkTitles[i],
                        url: linkUrls[i]
                    });
                }
            }

            // APIへ送信
            const apiFormData = new FormData();
            apiFormData.append('data', JSON.stringify(saveObj));
 const newPassword = formData.get('new_password');
            if (newPassword && newPassword.trim() !== '') {
                apiFormData.append('new_password', newPassword.trim());
            }

            const fileInput = document.querySelector('input[name="avatar_file"]');
            if (fileInput.files.length > 0) {
                apiFormData.append('avatar_file', fileInput.files[0]);
            }

            fetch('api.php', {
                method: 'POST',
                body: apiFormData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const alert = document.getElementById('success-alert');
                        alert.style.display = 'block';
                        setTimeout(() => {
                           alert.style.display = 'none';
                           if (newPassword && newPassword.trim() !== '') {
                                alert('パスワードを変更しました。再ログインしてください。');
                                window.location.href = 'logout.php';
                            } else {
                                location.reload();
                           }
                        }, 1000);
                        window.scrollTo(0, 0);
                    } else {
                        alert('保存に失敗しました: ' + (data.error || '不明なエラー'));
                    }
                })
                .catch(err => {
                    alert('通信エラーが発生しました。');
                    console.error(err);
                });
        });
    </script>

</body>

</html>