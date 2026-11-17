<?php
require_once 'functions.php';

// Vidage des variables de session
$_SESSION = [];

// Suppression du cookie de session s'il existe
if (ini_get('session.use_cookies')) {
    $parametresCookie = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametresCookie['path'],
        $parametresCookie['domain'],
        $parametresCookie['secure'],
        $parametresCookie['httponly']
    );
}

// Destruction définitive de la session
session_destroy();

// Redirection vers l'écran d'authentification
header('Location: index.php');
exit;
