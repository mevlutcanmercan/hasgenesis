<?php
include 'dB/database.php';
include 'bootstrap.php';
include 'auth.php';
session_start();
preventAccessIfLoggedIn(); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri al
    $userMail = $_POST['mail_users'];
    $userPassword = $_POST['password_users'];

    // Veritabanında bu kullanıcıyı kontrol et
    $sql = "SELECT id_users, password_users FROM users WHERE mail_users = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Veritabanı hatası: " . $conn->error);
    }
    $stmt->bind_param("s", $userMail);
    $stmt->execute();
    $stmt->bind_result($id_users, $hashed_password);
    
    if ($stmt->fetch()) {
        if (password_verify($userPassword, $hashed_password)) {
            // Şifre doğru, oturumu başlat
            $_SESSION['id_users'] = $id_users;
            session_regenerate_id(true);

            // SweetAlert2 ile başarı mesajı göster ve yönlendir
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
                            window.location.href = "/hasgenesis/index.php";
                        }
                    });
                </script>';
        } else {
            // Hatalı giriş, SweetAlert2 ile kullanıcıya uyarı göster
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
        // Kullanıcı bulunamadı, SweetAlert2 ile kullanıcıya uyarı göster
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
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/hasgenesis/css/login.css"> <!-- Stil dosyanızı ekleyin -->
    
    
</head>
<body>
    <div class="ustkisim">
    </div>

    
    
    <div class="wrapper">
        <form action="login.php" method="post">
            <div class="logo"><img src="./images/logo-has.png" alt=""></div>
            <hr class="cizgi">
            <h1>Giriş Yap</h1>
            <div class="input-box">
                <input type="email" name="mail_users" placeholder="Eposta Adresi" required>
                <i class='bx bxs-user' style="color:black"></i>
            </div>
            <div class="input-box">
                <input type="password" name="password_users" placeholder="Şifre" id="password" required>
                <i class='bx bxs-lock-alt' style="color:black"></i>
            </div>
            <div class="remember-forgot">
                <label><input type="checkbox" name="remember" class="checkbox-remember">Beni Hatırla</label>
                <a href="forgotpassword">Şifremi Unuttum</a>
            </div>
            <button type="submit" class="btn">Giriş Yap</button>
        </form>
        <a href="register.php"><button type="submit" class="btn">Kayıt Ol</button></a>
    </div>

    
</body>
</html>
