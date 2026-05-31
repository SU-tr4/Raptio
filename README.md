# 🚀 Raptio CMS

<p align="center">
  <img src="admin/img/logo1.png" width="300">
</p>

<p align="center">

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php)
![Flat File CMS](https://img.shields.io/badge/CMS-Flat%20File-success)
![Markdown](https://img.shields.io/badge/Markdown-Supported-blue)
![GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue)
![Status](https://img.shields.io/badge/Status-Development-orange)

</p>

---

## 📖 About

**Raptio CMS** は、日本製の軽量フラットファイルCMSです。

データベースを必要とせず、JSONファイルのみでサイトを構築・運用できます。

シンプルな設計と高速な動作を重視しながら、テーマやプラグインによる柔軟な拡張性も備えています。

※ 現在、最低限は使える状態ですがプラグイン機能がまだありません！
---

## ✨ Features

| 機能            | 内容          |
| ------------- | ----------- |
| 🗂 フラットファイル   | DB不要・JSON管理 |
| ⚡ 高速動作        | 軽量設計        |
| 📝 Markdown対応 | Parsedown採用 |
| 🎨 テーマ機能      | デザイン切替対応    |
| 🔌 プラグイン機能    | 機能拡張可能      |
| 🖼 メディア管理     | 画像アップロード対応  |
| 📚 カテゴリー管理    | 記事分類対応      |
| ⚙ 管理画面        | GUI操作対応     |
| 🇯🇵 日本語対応    | 日本語UI       |

---

## 🖥 Requirements

| 項目             | 要件   |
| -------------- | ---- |
| PHP            | 8.0+ |
| Apache         | 推奨   |
| Nginx          | 対応   |
| MySQL          | 不要   |
| PostgreSQL     | 不要   |
| JSON Extension | 必須   |

---

# 📦 Installation

## Git Clone

```bash
git clone https://github.com/yourname/raptio.git
cd raptio
```

## 権限設定

Linux

```bash
chmod -R 755 data
chmod -R 755 uploads
```

または

```bash
chown -R www-data:www-data data
chown -R www-data:www-data uploads
```

---

## 🚀 起動

ブラウザで管理画面へアクセス

```text
http://localhost/raptio/admin/
```

または

```text
https://your-domain.com/admin/
```

---

# 🏗 Architecture

```text
               +----------------+
               |  Admin Panel   |
               +--------+-------+
                        |
                        v
               +----------------+
               | JSON Storage   |
               +--------+-------+
                        |
         +--------------+--------------+
         |                             |
         v                             v
  posts/*.json              site_config.json
         |
         v
   posts_index.json
         |
         v
      Frontend
```

---

# 📂 Directory Structure

```text
raptio/
├── index.php
├── raptio_ファイル構成一覧.txt
│
├── admin/
│   ├── api.php
│   ├── auth.php
│   ├── categories.php
│   ├── cleanup.php
│   ├── config.php
│   ├── edit-posts.php
│   ├── editor.php
│   ├── index.php
│   ├── media.php
│   ├── plugin-helper.php
│   ├── site-menu.php
│   ├── site-settings.php
│   ├── site-sidebar.php
│   │
│   ├── css/
│   │   ├── admin_editor.css
│   │   ├── admin_settings.css
│   │   ├── admin_style.css
│   │   └── admin_widgets.css
│   │
│   ├── img/
│   │   ├── logo1.png
│   │   ├── logo2.png
│   │   └── Raptio_icon.png
│   │
│   └── includes/
│       ├── header.php
│       ├── sidebar.php
│       └── footer.php
│
├── data/
│   ├── posts/
│   ├── posts_index.json
│   └── site_config.json
│
├── includes/
│   └── Parsedown.php
│
├── plugins/
│
├── themes/
│   └── lux/
│       ├── header.php
│       ├── footer.php
│       ├── sidebar.php
│       ├── index.php
│       ├── single.php
│       └── style.css
│
└── uploads/
```

---

# 📝 Example Post Data

```json
{
  "id": 1,
  "title": "Hello Raptio",
  "slug": "hello-raptio",
  "category": "News",
  "created_at": "2026-05-31",
  "content": "# Hello World"
}
```

---

# 🎨 Theme Structure

```text
themes/
└── lux/
    ├── header.php
    ├── footer.php
    ├── sidebar.php
    ├── index.php
    ├── single.php
    └── style.css
```

---

# 🔌 Plugin Structure

```text
plugins/
└── my-plugin/
    ├── plugin.php
    ├── config.json
    └── assets/
```

---

# 📊 Data Flow

```text
記事作成
    ↓
editor.php
    ↓
api.php
    ↓
data/posts/
    ↓
posts_index.json
    ↓
Frontend表示
```

---

# 🛣 Roadmap

* [x] 記事管理
* [x] カテゴリー管理
* [x] メディア管理
* [x] テーマシステム
* [ ] プラグイン管理UI
* [ ] SEO設定
* [ ] REST API
* [ ] キャッシュ機能
* [ ] 自動アップデート
* [ ] バックアップ機能

---

# 🤝 Contributing

Issue・Pull Request歓迎です。

```bash
git checkout -b feature/new-feature
git commit -m "Add new feature"
git push origin feature/new-feature
```

---

# 📜 License

Raptio CMS is licensed under GPL v2.

```text
GNU GENERAL PUBLIC LICENSE Version 2
```

https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

---

# ❤️ Support

Raptio CMS が役に立った場合は GitHub Star をお願いします。

```bash
⭐ Star this repository
```
