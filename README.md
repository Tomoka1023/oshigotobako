# おしごと箱

PHPとMySQLで作成した、顧客・案件・タスクを管理できるシンプルなCRMアプリです。

## 概要

「おしごと箱」は、顧客情報・案件情報・タスクをまとめて管理するためのWebアプリです。  
ポートフォリオ用のアプリとして、ログイン機能、CRUD機能、管理者機能、操作ログ機能、CSV出力機能を実装しました。

## 主な機能

- ログイン / ログアウト
- 顧客管理
- 案件管理
- タスク管理
- 管理者によるユーザー管理
- 操作ログ記録
- 検索・絞り込み
- CSV出力
- スマホ表示対応
- 期限間近タスクのメール通知
  - 管理者メニューから手動送信
  - cron用PHPによる自動通知
  - 送信結果を操作ログに記録

## 使用技術

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- PDO
- PHPMailer
- Composer
- cron

## 工夫した点

- PDOのプリペアドステートメントを使用し、SQLインジェクション対策を行いました。
- 表示時には `h()` を使用し、XSS対策を意識しました。
- `url()` を使ってリンク生成を共通化しました。
- 顧客・案件・タスク・ユーザーの登録・編集・削除を操作ログとして記録できるようにしました。
- CSV出力は管理者のみ実行できるようにしました。
- スマホでも見やすいように、テーブルや検索フォームの表示を調整しました。
- PHPMailerを使用し、期限が近い未完了タスクを管理者宛にメール通知できるようにしました。
- cron実行用のPHPファイルを用意し、サーバー側で定期実行できる構成にしました。
- メール送信結果も操作ログに記録し、管理画面から確認できるようにしました。
- メール設定ファイルは `.gitignore` に追加し、GitHubへSMTP情報が公開されないようにしました。

## セットアップ方法

1. リポジトリをクローンします。

    git clone リポジトリURL

2. Composerの依存関係をインストールします。

    composer install

3. app/db.example.php をコピーして app/db.php を作成します。

    cp app/db.example.php app/db.php

4. app/db.php にデータベース情報を設定します。

5. app/mail_config.example.php をコピーして app/mail_config.php を作成します。

    cp app/mail_config.example.php app/mail_config.php

6. app/mail_config.php にSMTP情報を設定します。

7. database/schema.sql をMySQLにインポートします。

8. ブラウザでアクセスします。

    http://localhost:8888/oshigotobako/public/


## メール通知機能について

- このアプリでは、期限が近い未完了タスクを管理者宛にメール通知できます。

### 手動通知

- 管理者メニューから「期限間近タスクをメール通知」ボタンを押すことで、通知メールを送信できます。

### cronによる自動通知

- cron実行用のPHPファイルとして、以下を用意しています。
    cron/send_task_notice.php
- ローカル環境で手動実行する場合は、MAMPのPHPを指定して実行します。
    /Applications/MAMP/bin/php/php8.3.28/bin/php cron/send_task_notice.php
- 本番環境では、サーバーのcron機能からこのPHPファイルを定期実行する想定です。

## 注意事項

- このアプリはポートフォリオ用に作成したものです。
- 本番運用する場合は、CSRF対策、権限管理、入力バリデーション、監査ログ管理などをさらに強化する必要があります。
- app/db.php と app/mail_config.php には接続情報やSMTP情報を記載するため、GitHubには公開しないよう .gitignore に追加しています。