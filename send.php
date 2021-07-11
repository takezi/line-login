<?php
define('LINE_MESSAGE_PUSH_URL', 'https://api.line.me/v2/bot/message/push');
define('LINE_MESSAGE_CHANNEL_ACCESS_TOKEN', GetEnv('LINE_MESSAGE_CHANNEL_ACCESS_TOKEN'));
define('APP_MAIN_URL', 'index.php');

session_start();

// CSRF対策（<form>からの送信でなければ不要）
if($_SESSION['csrf_token'] != $_POST['_csrf']) {
    echo 'CSRFトークンが不正です。';
    exit;
}

// プッシュメッセージを送る
// See. https://developers.line.biz/ja/reference/messaging-api/#send-push-message

$body = [
    'to' => $_SESSION['user_id'],
    'messages' => [
        [
            'type' => 'text',
            'text' => $_POST['message']
        ]
    ]
];
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header'=> 'Authorization: Bearer ' . LINE_MESSAGE_CHANNEL_ACCESS_TOKEN . "\r\n"
                    . "Content-Type: application/json\r\n",
        'content' => json_encode($body, JSON_UNESCAPED_UNICODE),
        'ignore_errors' => true
    ]
]);
$res = json_decode(file_get_contents(LINE_MESSAGE_PUSH_URL, false, $context), true);
if (isset($res['message'])) {
    echo 'メッセージ送信に失敗しました：' . htmlspecialchars($res['message'], ENT_QUOTES, 'UTF-8');
    exit;
}

// メイン画面に戻る
header('Location: ' . APP_MAIN_URL);