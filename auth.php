<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'dB/database.php';

/**
 * Kullanıcının giriş yapmış olup olmadığını kontrol eder.
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['id_users']);
}

/**
 * Giriş yapmış kullanıcıları belirli sayfalara erişimden engeller.
 * @param string $redirectUrl Yönlendirme yapılacak URL
 */
function preventAccessIfLoggedIn($redirectUrl = 'index.php') {
    if (isLoggedIn()) {
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Giriş yapmamış kullanıcıları belirli sayfalara erişimden engeller.
 * @param string $redirectUrl Yönlendirme yapılacak URL
 */
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectUrl");
        exit();
    }
}
?>
