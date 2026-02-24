# Laravel Mail Scraper

複数サイトをスクレイピングして、**キーワードにマッチしたらメール通知**する Laravel アプリです。  
重複通知を避けるため、最新取得位置を DB（`scrape_histories`）に保存します。

## できること

- **TDnet 適時開示（当日分の一覧）**: `release`
- **TDnet トップページ（一覧）**: `tdnet_search`（アクセス拒否対策として User-Agent を付与）
- **JPX 上場廃止・整理等**: `jpx`
- **Yahoo天気（渋谷）**: `yahoo_weather`（前回もマッチ済みなら再送しない）

実行コマンドは `php artisan scrape:notify` です。

## 必要要件

- PHP 8.2+
- Composer
- DB（SQLite でも OK）
- メール送信設定（SMTP 等）

## セットアップ（最小）

1. 依存導入

```bash
composer install
```

2. `.env` 作成（未作成なら）

```bash
copy .env.example .env
php artisan key:generate
```

3. DB 設定 → マイグレーション

```bash
php artisan migrate
```

4. 通知先を設定（どちらか）

- `.env` に設定（推奨）
  - `SCRAPE_MAIL_TO=to1@example.com,to2@example.com`
  - `SCRAPE_MAIL_CC=cc1@example.com`
- または `config/scrape.php` の `mail_to`, `mail_cc`

5. メール送信設定（例: SMTP）

`.env` の `MAIL_*` を設定してください（Laravel 標準のメール設定です）。

## 使い方

### 手動実行

```bash
php artisan scrape:notify
```

- **マッチなし**: 何も送信せず終了します
- **送信先未設定**: ログに警告を出して送信せず終了します

### 定期実行（スケジューラ）

`routes/console.php` で `scrape:notify` が **30分ごと**に登録されています。

- サーバーで動かす場合: cron で `schedule:run` を 1 分ごとに回すのが一般的です

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

（ローカル検証なら `php artisan schedule:work` でも動きます）

## スクレイプ条件の設定

`config/scrape.php` を編集します。

- **release**
  - `sendtime`: `['HH:MM', 'HH:MM']`（当日分のみ、時間帯で絞り込み）
  - `keyword`: `[['含めたい語', '除外語1', '除外語2'], ...]`
    - 先頭が「含めたい語」、2つ目以降が「除外語」です（`-` は付いていてもいなくても動作します）
- **tdnet_search**
  - `keyword`: `['検索語1', '検索語2']`（空なら全件扱い）
- **jpx**
  - 追加設定なし（ページ先頭の新着から差分検知）
- **yahoo_weather**
  - `keyword`: `['語1', '語2']`（**すべて含まれる**ときだけマッチ）

## 通知メール

- メール本文: `resources/views/emails/scrape-notification.blade.php`
- 件名: 最初にマッチした記事タイトル（なければ `スクレイプ通知`）

## ログ / トラブルシュート

- **ログ**: `storage/logs/laravel.log`
- **サイト構造変更**でマッチしない場合があります（HTML構造が変わると抽出条件が外れます）
- **TDnet に弾かれる**場合があります（`tdnet_search` は拒否時の status/body をログに残します）
