# LINEログイン v2.1を使ったログイン(PKCEも対応)

## 概要

LINEログイン v2.1を使ったログインとメッセージ送信のPHPサンプルコードです。

## ファイル一覧

| ファイル名 | 説明
| --- | ---
| index.php | ログインしていなければ、LINEログインの処理を開始。ログインしていれば、セッションからユーザー情報を表示。
| callback.php | OAuth/OpenID ConnectのコールバックURLとして使用。アクセストークンの発行やIDトークンの取得を行う。
| send.php | Messaging APIを利用してログインユーザーにメッセージを送るサンプルコード。
| .htaccess | 環境に依存する情報を、環境変数に設定するために使用。実装に応じて別の方法で渡しても構わない。

## 事前準備

1. LINE Developersコンソールでチャネルを作成  
    https://developers.line.biz/ja/docs/messaging-api/getting-started/#using-console  
    ※「1. LINE Developersコンソールにログインする」から「5. チャネルを確認する」までを実施します。
1. 長期のチャネルアクセストークンを発行  
    ※LINE Developersコンソールの［チャネル設定］タブでMessaging APIチャネルを選択して、［Messaging API設定］タブで発行できます。
1. LINEログインのチャネルを作成  
    LINE Developersコンソールにアクセスし、プロバイダーを選択し、「新規チャネル作成」から追加します。  
    ※チャネルの種類は「LINEログイン」、アプリタイプは「ウェブアプリ」を選択します。
1. サンプルコードをローカルに配置  
    ※以降、ローカルではapacheを利用し、htdocsの下にline-loginという名前でクローンした前提で説明します。
1. ngrokをインストール  
    ローカルで確認を行うために使用します。レンタルサーバーなど本番環境では不要です。
    - Macの場合はbrewでインストールするのが簡単
        ```
        brew install ngrok
        ````
    - WindowsまたはMacでbrewが使えない場合  
        https://ngrok.com/download  
        からダウンロードして任意の場所に展開します。

## 環境変数の設定

.htaccess（またはhttpd.confなど）に、以下の値をLINE Developersコンソールで確認して設定します。

| 変数名 | 値
| --- | ---
| LINE_LOGIN_CHANNEL_ID | LINEログインのチャネルID
| LINE_LOGIN_CHANNEL_SECRET | LINEログインのチャネルシークレット
| LINE_LOGIN_CALLBACK_URL | callback.phpへのURLを指定（詳細は次の節で説明します）
| LINE_MESSAGE_CHANNEL_ACCESS_TOKEN | Messaging APIのチャネルアクセストークン（長期）

設定例
```
SetEnv LINE_LOGIN_CHANNEL_ID 1656201281
SetEnv LINE_LOGIN_CALLBACK_URL https://4c09b0b9afc6.ngrok.io/line-login/callback.php
SetEnv LINE_LOGIN_CHANNEL_SECRET c2663b9c8dc9327e63088e6bfe436f25
SetEnv LINE_MESSAGE_CHANNEL_ACCESS_TOKEN lUEdqPlxca9cJzhsJf4PLb0aonuxWZIsjUrUocCt+Oco2RqhFr1/1GkprxCDZ1zi1iYNZlFY8IRx58ZmV6Zhqk8+80f+xjp7WRZRqZiCfdl8RMgqKR1Nv/JWVFzOxiSNYHDdDRbN75LRMCvWL8AAzwdB04t89/1O/w1cDnyilFU=
```

## ngrokの起動とコールバックURL設定

1. ngrokを起動してhttpsのURLを控える
    ```
    ngrok http 80
    ```
    起動すると以下のように表示され、以下の例だと `https://ad336e08f4cd.ngrok.io` の部分を控えます。
    ```
    ngrok by @inconshreveable                                         (Ctrl+C to quit)
    Session Status                online
    Session Expires               51 minutes
    Version                       2.3.40
    Region                        United States (us)
    Web Interface                 http://127.0.0.1:4040
    Forwarding                    http://4c09b0b9afc6.ngrok.io -> http://localhost:80
    Forwarding                    https://4c09b0b9afc6.ngrok.io -> http://localhost:80
    ```
    ※URLはngrokを起動し直す度に変化するため、その度にコピーし直す必要があります。
1. コールバックURLの作成  
    ngrokのhttpsのURLに「/line-login/callback.php」を加えたものになります。  
    例）https://4c09b0b9afc6.ngrok.io/line-login/callback.php
1. コールバックURLを `.htaccess` の `LINE_LOGIN_CALLBACK_URL` に設定
1. コールバックURLをLINEログイン設定に設定  
    ※LINE Developersコンソールの［チャネル設定］タブでLINEログインチャネルを選択して、［LINEログイン設定］タブで設定できます。

## 動作確認

1. ngrokのhttpsのURLに「/line-login/index.php」を加えたものにアクセス
1. LINEにログイン  
    ※URLをLINEで送って、LINE上のブラウザで開いた場合はこのステップは省略されます。またPCでも既にログイン済みなら省略されます。
1. 「許可する」ボタンを押してアクセスを許可する
1. ユーザーIDと名前を確認できる画面が表示される  
    メッセージ入力欄と送信ボタンで任意のメッセージを送ることを試せます。

## うまく動かない場合

- 「400 Bad」(Request Invalid redirect_uri value. Check if it is registered in a LINE developers site.) と表示される  
    コールバックURLの設定が間違っています。
    ngrokのURLは起動する度に変化します。
    起動する度に、`.htaccess` と LINE DevelopersコンソールでコールバックURLを再設定する必要があります。  
    ※本番環境ではURLが変化しないため、一度設定すると変える必要はありません。
- 「400 Bad」(This channel is now developing status. User need to have developer role.) と表示される  
    LINEログインのチャネルが「非公開」（デフォルト）になっています。
    非公開の状態では権限設定で許可されたユーザーだけがログインできます。
    開発が完了したら「公開」にして任意のユーザーがログインできるようにします。
- 「認可サーバーが無効なstateパラメータを返却しました。」と表示される  
    間違ってcallback.phpにアクセスすると発生します。最初にアクセスするURLはindex.phpです。

## その他

- LINE公式アカウントの設定
    LINE DevelopersコンソールでMessaging APIチャネルを作成すると、自動的にLINE公式アカウントが作成されます。
    LINE公式アカウントの各種設定は [LINE Official Account Manager](https://manager.line.biz) から行います。
    例えば、以下のような設定を行うことも出来ます。
    - QRコードで友達登録したらindex.phpへのURLをチャットで送る
    - リッチメニューでindex.phpに飛べるようにする  
- ユーザーIDが使える範囲  
    ユーザーIDは、LINE Developersコンソールで最初に作成したプロバイダの単位でユニークです。  
    プロバイダが異なると同じユーザーでも異なるIDが割り振られます。
- LIFFについて  
    LINEログインを利用するには、LIFFというjsフレームワークを利用する方法もあります。
    | 方法 | 特徴 | 向いている使い方
    | --- | --- | ---
    | LIFF | jsで作られたLINEのフロントエンドフレームワーク | 主にフロントエンドで処理する場合
    | LINEログイン | OAuthとOpenID Connectに基づくログイン | 主にサーバーサイドで処理する場合

    LIFFで取得したユーザー情報をサーバーサイドで使用するには注意が必要です。  
    https://developers.line.biz/ja/docs/liff/using-user-profile/  
    このため、サーバーサイドで使う場合はLINEログインを利用した方が実装がスッキリする感じがします。
