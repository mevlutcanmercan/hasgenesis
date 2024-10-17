<?php
include 'dB/database.php';
include 'auth.php';

requireLogin();

    $user_id = $_SESSION['id_users']; 
    $registration_id = isset($_POST['registration_id']) ? intval($_POST['registration_id']) : null;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

    if ($registration_id && $reason) {
        // Sorguyu hazırlayıp çalıştırma
        $stmt = $conn->prepare("INSERT INTO cancellations (registration_id, user_id, reason) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $registration_id, $user_id, $reason);

        if ($stmt->execute()) {
            echo "İptal sebebiniz başarıyla iletildi.";
        } else {
            echo "Kayıt başarısız: " . $conn->error;  // Hata mesajını ekrana yazdır
        }
        $stmt->close();
    } else {
        echo "Kayıt başarısız: Geçersiz veri";
    }

header("Location: account.php");
exit();
?>
