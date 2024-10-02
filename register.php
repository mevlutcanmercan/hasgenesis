<?php
    include 'auth.php';
    include 'bootstrap.php';

    session_start();
    
    // Kullanıcı zaten giriş yapmışsa erişimi engelle
    preventAccessIfLoggedIn(); 
    
    $error = '';
    $success = '';
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Form verilerini al ve temizle
        $email = trim($_POST['mail_users']);
        $password = $_POST['password_users'];
        $name = trim($_POST['name_users']);
        $surname = trim($_POST['surname_users']);
        $telefon = trim($_POST['telefon']);
        $birthday = trim($_POST['birthday_users']);
    
        // Sunucu tarafı doğrulamaları
        
        // Doğum tarihi kontrolü (geçerli mi?)
        if (strtotime($birthday) > time()) {
            $error = "Geçersiz doğum tarihi.";
        }
    
        $phone_pattern = '/^\+?[1-9]\d{1,14}$/'; // 10 ila 14 haneli numaralar kabul edilir
        
        if (!preg_match($phone_pattern, $telefon)) {
            $error = "Telefon numarası formatı hatalı. Lütfen geçerli bir telefon numarası girin.";
        }
    
        // E-posta kontrolü (sadece önceki kontroller geçerse)
        if (empty($error)) {
            $check_email_query = "SELECT COUNT(*) as count FROM users WHERE mail_users = ?";
            $stmt_check_email = $conn->prepare($check_email_query);
            if (!$stmt_check_email) {
                $error = "Veritabanı hatası: " . $conn->error;
            } else {
                $stmt_check_email->bind_param("s", $email);
                $stmt_check_email->execute();
                $stmt_check_email->bind_result($email_count);
                $stmt_check_email->fetch();
                $stmt_check_email->close();
                
                
                if ($email_count > 0) {       
                    $error = "E-posta zaten kullanılıyor.";
                }
            }
        }
    
        // Hata yoksa kullanıcıyı ekle
        if (empty($error)) {
            // Şifreyi hash'le
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
            // Yeni kullanıcı ekle
            $sql_insert = "INSERT INTO users (password_users, mail_users, name_users, surname_users, telefon, birthday_users, isAdmin) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert) {
                $isAdmin = 0; 
                $stmt_insert->bind_param("ssssssi", $hashed_password, $email, $name, $surname, $telefon, $birthday, $isAdmin);
                
                if ($stmt_insert->execute()) {
                    $success = "Kayıt başarı ile tamamlandı, giriş sayfasına yönlendiriliyorsunuz...";
                } else {
                    $error = "Kayıt sırasında bir hata oluştu: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                $error = "Veritabanı hatası: " . $conn->error;
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="/hasgenesis/css/login.css?v=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- intl-tel-input CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/css/intlTelInput.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<!--
    intl-tel-input
    https://github.com/jackocnr/intl-tel-input
    Licensed under the MIT License
-->
<body>
    <div class="wrapper">
        <form action="register.php" method="post">
            <div class="back-button">
                <a href="login.php"><i class='bx bx-arrow-back' style="color: black;"></i></a>
            </div>
            <div class="logo"><img src="/hasgenesis/images/logo-has.png" alt="Logo"></div>
            <hr class="cizgi">
            <h1>Kayıt Ol</h1>
            
            <!-- Hata mesajlarını SweetAlert ile göster -->
            <?php if ($error): ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        Swal.fire({
                            icon: "error",
                            title: "Hata!",
                            text: "<?php echo addslashes($error); ?>",
                            confirmButtonText: "Tamam"
                        });
                    });
                </script>
            <?php endif; ?>
            
            <!-- Başarı mesajını SweetAlert ile göster -->
            <?php if ($success): ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        Swal.fire({
                            icon: "success",
                            title: "Başarılı!",
                            text: "<?php echo addslashes($success); ?>",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            willClose: () => {
                                window.location.href = "/hasgenesis/login.php";
                            }
                        });
                    });
                </script>
            <?php endif; ?>
            
            <div class="input-box">
                <input type="email" name="mail_users" placeholder="Eposta Adresi" required>
                <i class='bx bxs-user' style="color:black"></i>
            </div>
            <div class="input-box">
                <input type="password" name="password_users" placeholder="Şifre" required>
                <i class='bx bxs-lock-alt' style="color:black"></i>
            </div>
            <div class="input-box">
                <input type="text" name="name_users" id="name_users" placeholder="Adınız" required>
                <i class='bx bxs-user' style="color:black"></i>
            </div>
            <div class="input-box">
                <input type="text" name="surname_users" id="surname_users" placeholder="Soyadınız" required>
                <i class='bx bxs-user' style="color:black"></i>
            </div>
            <div class="input-box">
                <input type="tel" id="telefon" name="telefon" placeholder="Telefon" required>
                <i class='bx bxs-phone' style="color:black"></i>
            </div>
            <div class="input-box">
                <input type="date" name="birthday_users" placeholder="Doğum Tarihi" required>
                <i class='bx bxs-calendar' style="color:black"></i>
            </div>
            <button type="submit" class="btn">Kayıt Ol</button>
        </form>
    </div>
    
    <!-- jQuery ve intl-tel-input -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"></script>
    
 
    
    <!-- Register.js -->
    <script src="/js/register.js"></script>

</body>
</html>
