<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Hata raporlamayı etkinleştir
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PHPMailer dosyalarını dahil et
include 'PHPMailer/src/Exception.php';
include 'PHPMailer/src/PHPMailer.php';
include 'PHPMailer/src/SMTP.php';
include 'db/database.php'; // Veritabanı bağlantısı için gerekli dosya

$message = null; // Mesaj değişkenini tanımlayın

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $gemail = $_POST['eposta'];

    // Veritabanı bağlantısını kontrol edin
    if ($conn->connect_error) {
        die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
    }

    // Kullanıcının e-posta adresini kontrol et
    $query = $conn->prepare("SELECT id_users, name_users FROM users WHERE mail_users = ?");
    if ($query === false) {
        die("Hazırlanan ifadede hata: " . $conn->error);
    }
    
    $query->bind_param('s', $gemail);
    $query->execute();
    $query->bind_result($userId, $name);
    $query->fetch();

    // Sonuçları temizle
    $query->free_result();
    $query->close(); // Hazırlanan ifadeyi kapat

    if (!empty($userId)) {
        // Yeni bir şifre oluştur
        $newPassword = bin2hex(random_bytes(4)); // 8 karakterli rastgele bir şifre
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT); // Hash'le

        // Kullanıcının yeni şifresini veritabanına kaydet
        $updateQuery = $conn->prepare("UPDATE users SET password_users = ? WHERE id_users = ?");
        if ($updateQuery === false) {
            die("Hazırlanan ifadede hata: " . $conn->error);
        }

        $updateQuery->bind_param('si', $hashedPassword, $userId);
        $updateQuery->execute();
        
        // Güncelleme sorgusunun etkilediği satır sayısını kontrol et
        if ($updateQuery->affected_rows === 0) {
            $message = array(
                "type" => "error",
                "text" => "Şifre güncellenemedi."
            );
        } else {
            // E-posta gönderimi
            $mail = new PHPMailer(true);
            try {
                // SMTP ayarları
                $mail->isSMTP();
                $mail->Host = 'mail.hasgenesis.com'; // Giden posta sunucusu
                $mail->SMTPAuth = true;
                $mail->Username = 'forgotpassword@hasgenesis.com'; // Gönderen e-posta
                $mail->Password = 'pojHg3GjGXbs'; // E-posta hesabının şifresi
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL kullanımı
                $mail->Port = 465; // SSL portu

                // Gönderen bilgileri
                $mail->setFrom('forgotpassword@hasgenesis.com', 'Şifre Hatırlatma'); // Gönderen adı
                $mail->addAddress($gemail); // Alıcı e-posta

                // E-posta içeriği
                $mail->Subject = 'Yeni Şifre Oluşturuldu';
                $mail->Body = "Merhaba {$name},\n\nYeni şifreniz: {$newPassword}";

                // Karakter setini ayarlayın
                $mail->CharSet = 'UTF-8'; // UTF-8 karakter seti kullanımı

                // E-posta gönderimi
                $mail->send();
                $message = array(
                    "type" => "success",
                    "text" => "Yeni şifre e-posta ile gönderildi."
                );
            } catch (Exception $e) {
                $message = array(
                    "type" => "error",
                    "text" => "E-posta gönderilemedi. Hata: " . $mail->ErrorInfo
                );
            }
        }

        $updateQuery->close(); // Hazırlanan ifadeyi kapat
    } else {
        $message = array(
            "type" => "error",
            "text" => "Bu e-posta adresi ile kayıtlı bir kullanıcı bulunamadı."
        );
    }

    $conn->close(); // Veritabanı bağlantısını kapat
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="css/forgotpassword.css">
    <link rel="shortcut icon" href="images/logo-has.png" type="image/x-icon">
</head>
<body>

    <div class="wrapper">
        <form action="" method="POST">  
            <div class="back-button">
                <a href="login.php"><i class='bx bx-arrow-back' style="color: black;"></i></a>
            </div>

            <div class="logo"><img src="./images/logo-has.png" alt=""></div>
            <hr class="cizgi">
            <h1>Şifremi Unuttum</h1>
             
            <div class="input-box">
                <input type="email" name="eposta" placeholder="E-posta Giriniz" required>
                <i class='bx bx-envelope' style="color:black"></i>
            </div>

            <button type="submit" class="btn">Şifremi Gönder</button>
        </form>
    </div>

    <!-- Mesajı gösteren JavaScript -->
    <script>
        let message = <?php echo json_encode($message); ?>;
        if (message) {
            Swal.fire({
                title: message.type === 'success' ? "Başarılı!" : "Hata!",
                text: message.text,
                icon: message.type === 'success' ? "success" : "error",
                confirmButtonText: "Tamam",
            });
            
            // 3 saniye sonra yönlendirme yap
            setTimeout(() => {
                window.location.href = 'login.php'; // Yönlendirme adresini buraya ekleyin
            }, 2000); // 2000 milisaniye = 2 saniye
        }
    </script>

</body>
</html>
