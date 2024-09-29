<?php
include 'auth.php';
preventAccessIfLoggedIn(); 

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['mail_users']);
    $password = $_POST['password_users'];
    $name = trim($_POST['name_users']);
    $surname = trim($_POST['surname_users']);
    $telefon = trim($_POST['telefon']);
    $birthday = trim($_POST['birthday_users']);

    // E-posta kontrolü
    $check_email_query = "SELECT COUNT(*) as count FROM users WHERE mail_users = ?";
    $stmt_check_email = $conn->prepare($check_email_query);
    if (!$stmt_check_email) {
        die("Veritabanı hatası: " . $conn->error);
    }
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $result = $stmt_check_email->get_result();
    $row = $result->fetch_assoc();
    $email_count = $row['count'];
    $stmt_check_email->close();
    
    if ($email_count > 0) {       
        $error = "E-posta zaten kullanılıyor.";
    } else {
        // Şifreyi hash'le
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Yeni kullanıcı ekle
        $sql_insert = "INSERT INTO users (password_users, mail_users, name_users, surname_users, telefon, birthday_users, isAdmin) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        if ($stmt_insert) {
            $isAdmin = 0; 
            $stmt_insert->bind_param("ssssssi", $hashed_password, $email, $name, $surname, $telefon, $birthday, $isAdmin);
            
            if ($stmt_insert->execute()) {
                echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
                echo '<script>
                        Swal.fire({
                            icon: "success",
                            title: "Başarılı!",
                            text: "Kayıt başarı ile tamamlandı, giriş sayfasına yönlendiriliyorsunuz...",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            willClose: () => {
                                window.location.href = "/hasgenesis/login.php";
                            }
                        });
                      </script>';
                exit(); 
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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/hasgenesis/css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <form action="register.php" method="post">
            <div class="back-button">
                <a href="login.php"><i class='bx bx-arrow-back' style="color: black;"></i></a>
            </div>
            <div class="logo"><img src="./images/logo-has.png" alt=""></div>
            <hr class="cizgi">
            <h1>Kayıt Ol</h1>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
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
                <input type="text" name="name_users" placeholder="Adınız" required>
                <i class='bx bxs-user' style="color:black"></i>
            </div>
            <div class="input-box">
                <input type="text" name="surname_users" placeholder="Soyadınız" required>
                <i class='bx bxs-user' style="color:black"></i>
            </div>
            <div class="input-box">
                <input type="text" name="telefon" placeholder="Telefon" required>
                <i class='bx bxs-phone' style="color:black"></i>
            </div>
            <div class="input-box">
                <input type="date" name="birthday_users" placeholder="Doğum Tarihi">
                <i class='bx bxs-calendar' style="color:black"></i>
            </div>
            <button type="submit" class="btn">Kayıt Ol</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
