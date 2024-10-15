<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Hata raporlamayı etkinleştir
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'db/database.php'; // Veritabanı bağlantısı için gerekli dosya

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
            $_SESSION['message'] = array(
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
                $_SESSION['message'] = array(
                    "type" => "success",
                    "text" => "Yeni şifre e-posta ile gönderildi."
                );
            } catch (Exception $e) {
                $_SESSION['message'] = array(
                    "type" => "error",
                    "text" => "E-posta gönderilemedi. Hata: " . $mail->ErrorInfo
                );
            }
        }

        $updateQuery->close(); // Hazırlanan ifadeyi kapat
    } else {
        $_SESSION['message'] = array(
            "type" => "error",
            "text" => "Bu e-posta adresi ile kayıtlı bir kullanıcı bulunamadı."
        );
    }

    $conn->close(); // Veritabanı bağlantısını kapat

    // Yönlendirme yapılacak olan sayfa
    header("Location: forgotpassword.php");
    exit();
}

// Eğer burada, hatalı bir erişim olursa (doğrudan sendmail.php'ye erişim gibi), kullanıcıyı yönlendirin
header("Location: forgotpassword.php");
exit();
?>
