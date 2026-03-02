<?php
require_once 'config.php';

$data = [
    'profile' => ['name' => 'Name', 'bio' => '', 'avatar_url' => ''],
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

function get_icon_url($url)
{
    if (!$url)
        return '';
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? '';

    // すべてのサイトで Google Favicon API を使用するように統一
    return "https://www.google.com/s2/favicons?domain={$host}&sz=128";
}

function get_display_id($url)
{
    if (!$url)
        return '';
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? '';
    $path = trim($parsed['path'] ?? '', '/');
    if (empty($path))
        return '';

    $services = ['instagram.com', 'twitter.com', 'x.com', 'github.com'];
    foreach ($services as $service) {
        if (strpos($host, $service) !== false) {
            $parts = explode('/', $path);
            return '@' . $parts[0];
        }
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($data['profile']['name']) ?>
    </title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="container">
        <section class="profile-section">
            <?php if (!empty($data['profile']['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($data['profile']['avatar_url']) ?>" alt="Avatar" class="avatar">
            <?php else: ?>
                <div class="avatar"></div>
            <?php endif; ?>
            <h1 class="name"><?= htmlspecialchars($data['profile']['name']) ?></h1>
            <p class="bio"><?= nl2br(htmlspecialchars(trim($data['profile']['bio']))) ?></p>
        </section>

        <?php if (!empty($data['links'])): ?>
            <section class="links-section">
                <?php foreach ($data['links'] as $link): ?>
                    <?php
                    $icon_url = get_icon_url($link['url']);
                    $display_id = get_display_id($link['url']);
                    ?>
                    <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer"
                        class="link-button">
                        <?php if ($icon_url): ?>
                            <img src="<?= htmlspecialchars($icon_url) ?>" class="link-icon" alt=""
                                onerror="this.src='https://www.google.com/s2/favicons?domain=<?= parse_url($link['url'], PHP_URL_HOST) ?>&sz=128'; this.onerror=null;">
                        <?php endif; ?>
                        <div class="link-text">
                            <span class="link-title"><?= htmlspecialchars($link['title']) ?></span>
                            <?php if ($display_id): ?>
                                <span class="link-id"><?= htmlspecialchars($display_id) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="embeds-section">
            <?php if (!empty($data['embeds']['instagram'])): ?>
                <?php $insta_id = ltrim($data['embeds']['instagram'], '@'); ?>
                <div class="embed-container instagram-embed">
                    <blockquote class="instagram-media"
                        data-instgrm-permalink="https://www.instagram.com/<?= htmlspecialchars($insta_id) ?>/"
                        data-instgrm-version="14"></blockquote>
                    <script async src="//www.instagram.com/embed.js"></script>
                </div>
            <?php endif; ?>
            <?php if (!empty($data['embeds']['x'])): ?>
                <?php $x_id = ltrim($data['embeds']['x'], '@'); ?>
                <div class="embed-container x-embed">
                    <a class="twitter-timeline" data-height="600"
                        href="https://twitter.com/<?= htmlspecialchars($x_id) ?>?ref_src=twsrc%5Etfw">Tweets by
                        <?= htmlspecialchars($x_id) ?></a>
                    <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <a href="login.php" class="admin-link">Admin</a>

</body>

</html>