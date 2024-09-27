<?php
include 'auth.php';

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
preventAccessIfLoggedIn('/hasgenesis/index.php'); 

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri al
    $userMail = trim($_POST['mail_users']);
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
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
            echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "Başarılı!",
                        text: "Giriş başarılı, ana sayfaya yönlendiriliyorsunuz...",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        willClose: () => {
                            window.location.href = "/hasgenesis/index.php";
                        }
                    });
                </script>';
        } else {
            $error = "Geçersiz şifre!";
        }
    } else {
        $error = "Kullanıcı bulunamadı!";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/hasgenesis/css/login.css"> <!-- Stil dosyanızı ekleyin -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="ustkisim">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <div class="wrapper">
        <form action="login.php" method="post">
            <div class="logo"><img src="./images/logo-empty.png" alt=""></div>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" ></script>
</body>
</html>
