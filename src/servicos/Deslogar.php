<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
session_unset();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 3600,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();


header("Location: index.php?rota=login");
exit;
?>