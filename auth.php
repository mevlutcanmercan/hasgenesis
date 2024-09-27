<?php

include 'dB/database.php';

function preventAccessIfLoggedIn($redirectUrl = '/hasgenesis/index.php') {
    if (isset($_SESSION['id_users'])) {
        header("Location: $redirectUrl");
        exit();
    }
}

// Giriş yapılmamış kullanıcıları engellemek için
function requireLogin($redirectUrl = '/hasgenesis/login.php') {
    if (!isset($_SESSION['id_users'])) {
        header("Location: $redirectUrl");
        exit();
    }
}
?>
