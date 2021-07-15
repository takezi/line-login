<?php
define('LINE_LOGIN_AUTHORIZE_URL', 'https://access.line.me/oauth2/v2.1/authorize');
define('LINE_LOGIN_CHANNEL_ID', GetEnv('LINE_LOGIN_CHANNEL_ID'));
define('LINE_LOGIN_CALLBACK_URL', GetEnv('LINE_LOGIN_CALLBACK_URL'));
define('LINE_LOGIN_SCOPE', 'profile openid');

session_start();

// ログインしていない場合は、認可URLにリダイレクト
if(!isset($_SESSION['user_id'])) {

    // ユーザーに認証と認可を要求する(PKCEにも対応)
    // See. https://developers.line.biz/ja/docs/line-login/integrate-line-login/#making-an-authorization-request

    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));
    $_SESSION['oauth_nonce'] = bin2hex(random_bytes(16));
    $_SESSION['oauth_code_verifier'] = base64url_encode(random_bytes(32));

    $url = LINE_LOGIN_AUTHORIZE_URL . '?' . http_build_query([
        'response_type' => 'code',
        'client_id' => LINE_LOGIN_CHANNEL_ID,
        'redirect_uri' => LINE_LOGIN_CALLBACK_URL,
        'state' => $_SESSION['oauth_state'],
        'scope' => LINE_LOGIN_SCOPE,
        'nonce' => $_SESSION['oauth_nonce'],
        'code_challenge' => base64url_encode(hash('sha256', $_SESSION['oauth_code_verifier'], true)),
        'code_challenge_method' => 'S256'
    ]);

    // 認可URLにリダイレクト
    header("Location: $url");

    // ユーザーによる認証と認可のプロセスが始まる
    // 完了すると、LINE_LOGIN_CALLBACK_URLにリダイレクトされる
    exit;
}

// Base64URLエンコードするための関数
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// ログインしている場合は、以下が表示される
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>メイン画面</h1>
    <ul>
        <li>ユーザーID：<?php echo $_SESSION['user_id']; ?></li>
        <li>名前：<?php echo $_SESSION['name']; ?></li>
    </ul>
    <form method="POST" action="send.php">
        <input type="hidden" name="_csrf" value="<?php echo $_SESSION['csrf_token']; ?>">
        <p>ログインユーザーにメッセージを送る（Messaging APIチャネル作成時に自動的に追加されたLINE公式アカウントに、予め友だち登録が必要）</p>
        <textarea name="message" cols="80" rows="5"></textarea><br>
        <input type="submit" value="送信">
    </form>
</body>
</html>
