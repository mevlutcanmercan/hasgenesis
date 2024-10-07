<?php
include 'dB/database.php';
include 'bootstrap.php';
include 'auth.php';
session_start();

// Kullanıcı zaten oturum açmışsa, ana sayfaya yönlendir.
preventAccessIfLoggedIn();

// Çerezlerdeki verileri kontrol et ve form alanlarına aktar
if (isset($_COOKIE['mail_users']) && isset($_COOKIE['password_users'])) {
    $userMail = $_COOKIE['mail_users'];
    $userPassword = $_COOKIE['password_users'];
} else {
    $userMail = '';
    $userPassword = '';
}

// POST isteği kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri al
    $userMail = $_POST['mail_users'];
    $userPassword = $_POST['password_users'];

    // Veritabanında bu kullanıcıyı kontrol et
    $sql = "SELECT id_users, password_users FROM users WHERE mail_users = ?";
    $stmt = $conn->prepare($sql);

    // Eğer sorgu hazırlanamıyorsa hata göster
    if (!$stmt) {
        die("Veritabanı hatası: " . $conn->error);
    }

    // Kullanıcının e-posta adresini bağla ve sorguyu çalıştır
    $stmt->bind_param("s", $userMail);
    $stmt->execute();

    // Sonuçları değişkenlere bağla
    $stmt->bind_result($id_users, $hashed_password);

    // Sonuçları al
    if ($stmt->fetch()) {
        // Girilen şifre ile veritabanındaki şifreyi karşılaştır
        if (password_verify($userPassword, $hashed_password)) {
            // Şifre doğruysa, oturumu başlat ve kullanıcıyı yönlendir
            $_SESSION['id_users'] = $id_users;
            session_regenerate_id(true); // Oturum ID'sini yenile

            // Beni Hatırla seçeneği işaretlenmişse
            if (isset($_POST['remember'])) {
                // Çerezleri 30 gün boyunca sakla
                setcookie('mail_users', $userMail, time() + (86400 * 30), "/");
                setcookie('password_users', $userPassword, time() + (86400 * 30), "/"); // Güvenlik açığına dikkat edin
            } else {
                // Çerezleri temizle
                setcookie('mail_users', '', time() - 3600, "/");
                setcookie('password_users', '', time() - 3600, "/");
            }

            // Başarı mesajı ve yönlendirme
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
            echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "Başarılı!",
                        text: "Giriş başarılı, ana sayfaya yönlendiriliyorsunuz...",
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        didClose: () => {
                            window.location.href = "index.php";
                        }
                    });
                </script>';
        } else {
            // Hatalı şifre, kullanıcıya uyarı göster
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
            echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Hata!",
                        text: "Hatalı şifre veya e-posta adresi!",
                        confirmButtonText: "Tamam"
                    });
                </script>';
        }
    } else {
        // Kullanıcı bulunamadıysa, kullanıcıya uyarı göster
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
        echo '<script>
                Swal.fire({
                    icon: "error",
                    title: "Hata!",
                    text: "Böyle bir kullanıcı bulunamadı!",
                    confirmButtonText: "Tamam"
                });
            </script>';
    }

    // Bağlantıları kapat
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <!-- Boxicons, Bootstrap ve Stil Dosyaları -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/login.css"> <!-- Kendi stil dosyanızı buraya ekleyin -->
    <link rel="shortcut icon" href="images/logo-has.png" type="image/x-icon">
</head>
<body>
    <!-- Üst Kısım -->
    <div class="ustkisim">
        <!-- Boş bırakılmış ya da logo eklenebilir -->
    </div>
    
    <!-- Giriş Formu -->
    <div class="wrapper">
        <form action="login.php" method="post">
            <div class="logo">
                <img src="./images/logo-has.png" alt="Logo"> <!-- Logonuzu buraya ekleyin -->
            </div>
            <hr class="cizgi">
            <h1>Giriş Yap</h1>
            
            <!-- Kullanıcı Girişi -->
            <div class="input-box">
                <input type="email" name="mail_users" placeholder="Eposta Adresi" value="<?php echo htmlspecialchars($userMail); ?>" required>
                <i class='bx bxs-user' style="color:black"></i>
            </div>
            
            <!-- Şifre Girişi -->
            <div class="input-box">
                <input type="password" name="password_users" placeholder="Şifre" value="<?php echo htmlspecialchars($userPassword); ?>" id="password" required>
                <i class='bx bxs-lock-alt' style="color:black"></i>
            </div>
            
            <!-- Hatırlatma ve Şifre Unutma -->
            <div class="remember-forgot">
                <label>
                    <input type="checkbox" name="remember" class="checkbox-remember" <?php echo isset($_COOKIE['mail_users']) ? 'checked' : ''; ?>>Beni Hatırla
                </label>
                <a href="forgotpassword">Şifremi Unuttum</a>
            </div>
            
            <!-- Giriş Yap Butonu -->
            <button type="submit" class="btn">Giriş Yap</button>
        </form>

        <!-- Kayıt Ol Butonu -->
        <a href="register.php">
            <button type="button" class="btn">Kayıt Ol</button>
        </a>
    </div>
</body>
</html>