<?php
define('LINE_LOGIN_TOKEN_URL', 'https://api.line.me/oauth2/v2.1/token');
define('LINE_LOGIN_VERIFY_URL', 'https://api.line.me/oauth2/v2.1/verify');
define('LINE_LOGIN_CHANNEL_ID', GetEnv('LINE_LOGIN_CHANNEL_ID'));
define('LINE_LOGIN_CHANNEL_SECRET', GetEnv('LINE_LOGIN_CHANNEL_SECRET'));
define('LINE_LOGIN_CALLBACK_URL', GetEnv('LINE_LOGIN_CALLBACK_URL'));
define('APP_MAIN_URL', 'index.php');

session_start();

// 認可コードまたはエラーレスポンスを受け取る
// See. https://developers.line.biz/ja/docs/line-login/integrate-line-login/#receiving-the-authorization-code-or-error-response-with-a-web-app

// stateの検証
if($_SESSION['oauth_state'] != $_GET['state']) {
    echo '認可サーバーが無効なstateパラメータを返却しました。';
    exit;
}

// エラーチェック
if(isset($_GET['error'])) {
    echo '<p>許可サーバーがエラーを返しました：' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p>' . htmlspecialchars($_GET['error_description'], ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}

// アクセストークンを発行する
// See. https://developers.line.biz/ja/reference/line-login/#issue-access-token

$body = [
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => LINE_LOGIN_CALLBACK_URL,
    'client_id' => LINE_LOGIN_CHANNEL_ID,
    'client_secret' => LINE_LOGIN_CHANNEL_SECRET,
    'code_verifier' => $_SESSION['oauth_code_verifier']
];
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($body, '', '&')
    ]
]);
$res = json_decode(file_get_contents(LINE_LOGIN_TOKEN_URL, false, $context), true);

// IDトークンを検証する
// See. https://developers.line.biz/ja/reference/line-login/#verify-id-token

$body = [
    'id_token' => $res['id_token'],
    'client_id' => LINE_LOGIN_CHANNEL_ID,
    'nonce' => $_SESSION['oauth_nonce']
];
unset($_SESSION['oauth_nonce']);
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($body, '', '&'),
        'ignore_errors' => true
    ]
]);
$res = json_decode(file_get_contents(LINE_LOGIN_VERIFY_URL, false, $context), true);
if (isset($res['error'])) {
    echo 'IDトークンの検証に失敗しました：' . htmlspecialchars($res['error_description'], ENT_QUOTES, 'UTF-8');
    exit;
}

// ログインセッションを設定する
session_regenerate_id(true);
$_SESSION = [];
$_SESSION['user_id'] = $res['sub'];
$_SESSION['name'] = $res['name'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));

// メイン画面に戻る
header('Location: ' . APP_MAIN_URL);
?>
