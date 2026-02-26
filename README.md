# qlo.jp

Personal website project for displaying profile, links, and social media embeds.

## Features

- **Profile Management**: Simple interface to edit name, bio, and upload profile images.
- **Image History**: Keeps the last 5 uploaded avatar images for easy restoration.
- **Link Display**: Automatically fetches icons (favicon/avatar) and displays SNS display IDs where available.
- **Auto Link Enrichment**:
  - Automatically prepends `https://` to URLs if missing.
  - Automatically fetches the site `<title>` for links if left blank.
- **Social Media Integration**: Easy embedding of Instagram posts.
- **Admin Dashboard**: Secure access to manage all content with password hashing.

## Tech Stack

- **PHP**: Core logic and server-side rendering.
- **JSON**: Flat-file database for configuration and content storage.
- **CSS**: Modern, responsive design without external frameworks.

## Setup

1. Deploy the files to a PHP-supported web server.
2. Ensure the `data/` directory is writable by the web server.
3. Access `index.php` for the public page and `admin.php` for editing.
4. Default password is set in `config.php`. (Needs to be a PHP password hash).
