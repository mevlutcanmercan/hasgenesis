<?php

include 'dB/database.php';

function preventAccessIfLoggedIn($redirectUrl = 'index.php') {
    if (isset($_SESSION['id_users'])) {
        header("Location: $redirectUrl");
        exit();
    }
}

// Giriş yapılmamış kullanıcıları engellemek için
function requireLogin($redirectUrl = 'login.php') {
    if (!isset($_SESSION['id_users'])) {
        header("Location: $redirectUrl");
        exit();
    }
}

// Admin kontrolü fonksiyonu
function requireAdmin($redirectUrl = 'index.php') {
    // Oturumda kullanıcı kimliği var mı kontrol et
    if (isset($_SESSION['id_users'])) {
        global $conn; // Veritabanı bağlantısını global olarak kullan

        // Kullanıcı bilgilerini al
        $userId = $_SESSION['id_users'];
        $query = "SELECT isAdmin FROM users WHERE id_users = ?";
        
        // Prepare statement
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId); // Kullanıcı ID'sini bağla
        $stmt->execute();
        $result = $stmt->get_result();

        // Kullanıcı bulundu mu kontrol et
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['isAdmin'] != 1) {
                header("Location: $redirectUrl"); // Admin değilse yönlendir
                exit();
            }
        } else {
            header("Location: $redirectUrl"); // Kullanıcı bulunamadı
            exit();
        }
    } else {
        header("Location: $redirectUrl"); // Oturum açmamışsa yönlendir
        exit();
    }
}
?>
