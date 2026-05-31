# H1: マークダウン全記法デモ記事

## H2: このファイルについて

このファイルはCMSテーマ開発用のデモ記事です。マークダウンで使えるすべての記法を網羅しています。テーマのスタイルが正しく当たっているか確認するために使ってください。

---

## H2: 見出し一覧

# H1: 見出し レベル1
## H2: 見出し レベル2
### H3: 見出し レベル3
#### H4: 見出し レベル4
##### H5: 見出し レベル5
###### H6: 見出し レベル6

---

## H2: テキスト装飾

通常のテキスト。日本語と English が混在する段落です。テーマのフォントや行間、文字間隔を確認できます。

**太字（ボールド）**で強調されたテキストです。

*イタリック（斜体）*で強調されたテキストです。

***太字かつイタリック***で強調されたテキストです。

~~取り消し線~~が引かれたテキストです。

通常の文の中に `インラインコード` が入っています。

通常の文の中に**太字**と*イタリック*と`コード`が**混在**している段落です。これはテーマの inline スタイルを確認するためのサンプルです。

---

## H2: 段落と改行

これは最初の段落です。段落と段落の間には空行が1行あります。マージンやパディングの確認に使ってください。

これは2番目の段落です。少し長めのテキストを入れています。Markdownでは段落は空行で区切ります。同じ段落内で改行したい場合は行末にスペースを2つ入れるか、バックスラッシュを使います。

これは3番目の段落です。

---

## H2: 引用（Blockquote）

> これは1行の引用です。

> これは複数行にわたる引用です。
> 2行目です。
> 3行目です。

> ### 引用の中に見出し
>
> 引用の中に段落があります。
>
> > ネストされた引用です。二重引用符のスタイルを確認できます。

---

## H2: リスト

### H3: 順序なしリスト（ul）

- 項目A
- 項目B
- 項目C
  - ネストされた項目C-1
  - ネストされた項目C-2
    - さらにネストされた項目C-2-a
    - さらにネストされた項目C-2-b
  - ネストされた項目C-3
- 項目D

### H3: 順序付きリスト（ol）

1. 最初の手順
2. 次の手順
3. その次の手順
   1. サブ手順 3-1
   2. サブ手順 3-2
4. 最後の手順

### H3: タスクリスト（チェックボックス）

- [x] 完了済みのタスク
- [x] こちらも完了
- [ ] 未完了のタスク
- [ ] こちらも未完了

### H3: リスト内に複数段落

- この項目には複数の段落があります。

  2つ目の段落です。インデントを合わせることでリストの一部として扱われます。

- 通常の項目に戻ります。

---

## H2: コードブロック

### H3: インラインコード

変数 `const name = "hello"` をそのまま使えます。

### H3: フェンスコードブロック（言語指定なし）

```
これはシンプルなコードブロックです。
言語指定なし。
```

### H3: Bash

```bash
# リポジトリをクローン
git clone https://github.com/example/repo.git
cd repo

# 依存関係インストール
npm install

# 開発サーバー起動
npm run dev
```

### H3: JavaScript

```javascript
// フロントマターをパースする例
const matter = require('gray-matter');
const fs = require('fs');

const fileContents = fs.readFileSync('article.md', 'utf8');
const { data, content } = matter(fileContents);

console.log(data.title);   // => "記事タイトル"
console.log(data.date);    // => 2026-06-01
console.log(content);      // => 本文のマークダウン文字列
```

### H3: TypeScript

```typescript
interface FrontMatter {
  title: string;
  date: Date;
  tags: string[];
  draft: boolean;
}

function parsePost(slug: string): { meta: FrontMatter; content: string } {
  const raw = fs.readFileSync(`posts/${slug}.md`, 'utf8');
  const { data, content } = matter(raw);
  return { meta: data as FrontMatter, content };
}
```

### H3: HTML

```html
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>デモページ</title>
</head>
<body>
  <article>
    <h1>記事タイトル</h1>
    <p>本文テキスト</p>
  </article>
</body>
</html>
```

### H3: CSS

```css
/* 基本リセット */
*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Noto Sans JP', sans-serif;
  font-size: 16px;
  line-height: 1.8;
  color: #1a1a1a;
}

h1, h2, h3 {
  font-weight: 700;
  line-height: 1.3;
}
```

### H3: YAML（フロントマター）

```yaml
---
title: "マークダウン全記法デモ記事"
date: 2026-06-01
author: 山田 太郎
tags:
  - markdown
  - cms
  - theme
draft: false
---
```

### H3: JSON

```json
{
  "title": "デモ記事",
  "date": "2026-06-01",
  "tags": ["markdown", "cms", "theme"],
  "draft": false,
  "views": 1024
}
```

---

## H2: 水平線（HR）

3種類の書き方、いずれも同じ結果になります。

---

***

___

---

## H2: リンク

[インラインリンク](https://example.com)

[タイトル付きリンク](https://example.com "例のサイト")

[同一ページの別セクションへのリンク](#h2-見出し一覧)

参照スタイルのリンク: [参照リンクのテキスト][ref-label]

[ref-label]: https://example.com "参照先のタイトル"

URLの自動リンク: <https://example.com>

メールの自動リンク: <hello@example.com>

---

## H2: 画像

![代替テキスト](https://placehold.co/800x400?text=Demo+Image "画像のタイトル")

参照スタイルの画像:

![参照画像][img-ref]

[img-ref]: https://placehold.co/400x300?text=Reference+Image "参照画像のタイトル"

リンク付き画像:

[![クリッカブルな画像](https://placehold.co/200x100?text=Clickable)](https://example.com)

---

## H2: テーブル

### H3: 基本テーブル

| 名前     | 役割         | 所属     |
|----------|--------------|----------|
| 山田 太郎 | フロントエンド | 開発部   |
| 鈴木 花子 | デザイナー   | デザイン部 |
| 田中 一郎 | バックエンド | 開発部   |

### H3: 文字揃えの指定

| 左寄せ   | 中央寄せ   | 右寄せ   |
|:---------|:----------:|---------:|
| テキスト | テキスト   | テキスト |
| 長いテキストサンプル | 中央 | 999,999 |
| 短い | 中央のテキスト | 42 |

### H3: テーブル内の装飾

| 記法               | 使用例                         | 説明           |
|--------------------|-------------------------------|----------------|
| `**太字**`         | **重要**                      | ボールド       |
| `*イタリック*`     | *強調*                        | 斜体           |
| `` `コード` ``     | `const x = 1`                 | インラインコード |
| `[リンク](url)`    | [例](https://example.com)     | ハイパーリンク  |

---

## H2: 脚注

これは脚注付きのテキストです[^1]。別の脚注もあります[^note]。

[^1]: これが最初の脚注の内容です。

[^note]: これが名前付き脚注の内容です。複数行にわたる場合は
    インデントを揃えます。

---

## H2: 定義リスト（拡張記法）

マークダウン
: テキストをHTMLに変換する軽量マークアップ言語。

CMS
: コンテンツ管理システム（Content Management System）の略。

フロントマター
: マークダウンファイルの先頭に書くYAML形式のメタデータ。

---

## H2: 数式（KaTeX / MathJax）

インライン数式: $E = mc^2$

ブロック数式:

$$
\int_{-\infty}^{\infty} e^{-x^2} dx = \sqrt{\pi}
$$

$$
\frac{\partial f}{\partial x} = \lim_{h \to 0} \frac{f(x+h) - f(x)}{h}
$$

---

## H2: 絵文字

:smile: :thumbsup: :rocket: :fire: :star:

---

## H2: HTMLの直書き（Raw HTML）

<div style="padding: 1rem; border: 2px dashed #ccc;">
  <strong>これはHTMLを直接書いたブロックです。</strong><br>
  マークダウン内にHTMLを埋め込めます。
</div>

<details>
  <summary>クリックして展開（折りたたみ）</summary>
  <p>これは折りたたまれた内容です。<code>details</code>タグと<code>summary</code>タグで実現します。</p>
</details>

---

## H2: 長い段落（リーダビリティ確認用）

Markdownは2004年にJohn GruberとAaron Swartzによって作成されました。その目的は「できる限り読み書きしやすいプレーンテキスト書式を使用してフォーマットを行い、構造的に妥当なXHTMLまたはHTMLに変換できるようにすること」でした。

現在ではGitHub、Reddit、Stack Overflow、そして多くのCMSプラットフォームで標準的に採用されています。CommonMarkやGitHub Flavored Markdown（GFM）など、仕様を標準化しようとする試みも進んでいます。

テーマ開発においては、長い段落のフォントサイズ、行間（line-height）、最大幅（max-width）、文字色などが読みやすさに大きく影響します。このセクションはそれらの値が適切かどうかを確認するために使ってください。

---

## H2: まとめ

このデモ記事には以下の記法が含まれています。

- 見出し（H1〜H6）
- テキスト装飾（太字・斜体・取り消し線・インラインコード）
- 段落・引用
- 順序なし・順序付き・タスクリスト
- コードブロック（複数言語）
- 水平線
- リンク・画像
- テーブル
- 脚注
- 定義リスト
- 数式
- 絵文字
- Raw HTML（details/summary含む）

---

*最終更新: 2026-06-01*