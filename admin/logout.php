<?php
session_start();

// Pastro të dhënat e sesionit
$_SESSION = [];

// Fshije cookie-n e sesionit
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Mbylle sesionin
session_destroy();

// Kthehu te faqja e kyçjes që gjendet brenda admin
header('Location: login.php');
exit;