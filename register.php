<?php
    include 'auth.php';
    include 'bootstrap.php';
    require 'vendor/autoload.php'; 

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    // PHPMailer dosyalarını dahil et
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';

    session_start();
    
    // Kullanıcı zaten giriş yapmışsa erişimi engelle
    preventAccessIfLoggedIn(); 

    $error = '';
    $success = '';
    
    $temp_email_domains = [
        'tempmail.com', '10minutemail.com', 'mailinator.com', 'guerrillamail.com'
    ];


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Form verilerini al ve temizle
        $email = trim($_POST['mail_users']);
        $password = $_POST['password_users'];
        $name = trim($_POST['name_users']);
        $surname = trim($_POST['surname_users']);
        $telefon = trim($_POST['telefon']);
        $birthday = trim($_POST['birthday_users']);
        $sex = trim($_POST['sex']);

        // Rate limiting mekanizması 
    if (!isset($_SESSION['activation_requests'])) {
        $_SESSION['activation_requests'] = [];
    }

    if (!isset($_SESSION['activation_total'])) {
        $_SESSION['activation_total'] = [];
    }

    if (!isset($_SESSION['activation_total'][$email])) {
        $_SESSION['activation_total'][$email] = 0;
    }

    // Eski istekleri temizleme (5 dakikadan eski olanları kaldır)
    $_SESSION['activation_requests'] = array_filter($_SESSION['activation_requests'], function ($timestamp) {
        return $timestamp > time() - 600; // 300 saniye = 10 dakika
    });

    // Aynı e-posta için kaç kez istek yapıldığını say
    $email_requests = array_count_values($_SESSION['activation_requests']);
    $email_request_count = $email_requests[$email] ?? 0;

    // Eğer rate limit aşıldıysa, hata döndür
    if ($email_request_count >= 2 || $_SESSION['activation_total'][$email] >= 3) {
        $error = "Çok fazla aktivasyon kodu istediniz. Lütfen daha sonra tekrar deneyin.";
    }
        

        // E-posta geçici mi?
        $email_domain = substr(strrchr($email, "@"), 1);
        if (in_array($email_domain, $temp_email_domains)) {
            $error = "Geçici e-posta adresleri kabul edilmiyor!";
        }

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
    
        if (empty($error)) {

                        // Yeni isteği kaydet
            $_SESSION['activation_requests'][] = time();
            $_SESSION['activation_total'][$email]++;

            // Aktivasyon kodu oluştur
            $activation_code = rand(100000, 999999);
    
            // Aktivasyon kodunu e-posta ile gönder
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; 
                $mail->SMTPAuth = true;
                $mail->Username = 'mercanmevlutcan@gmail.com';
                $mail->Password = 'thgupyzldbpbxjcq';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom('mercanmevlutcan@gmail.com', 'Kayıt Onayı');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = "Kayıt Aktivasyon Kodu";
                $mail->Body = "<p>Merhaba <strong>$name</strong>,</p>
                               <p>Kaydınızı tamamlamak için aşağıdaki kodu girin:</p>
                               <h2>$activation_code</h2>
                               <p>Bu kod 3 dakika geçerlidir.</p>";
    
                $mail->send();
    
                // Aktivasyon kodunu session'a kaydet
                $_SESSION['activation_code'] = $activation_code;
                $_SESSION['user_data'] = compact("email", "password", "name", "surname", "telefon", "birthday", "sex");
    
                // Aktivasyon kodu penceresini aç
                echo "<script>
                document.addEventListener('DOMContentLoaded', function () {
                    let attemptCount = 0; // Kullanıcı giriş deneme sayacı
            
                    function showActivationPopup() {
                        Swal.fire({
                            title: 'Aktivasyon Kodu',
                            input: 'text',
                            inputLabel: 'E-posta adresinize gelen kodu girin:',
                            inputPlaceholder: '6 haneli kodu girin',
                            showCancelButton: false,
                            confirmButtonText: 'Onayla',
                            allowOutsideClick: false,
                            preConfirm: (code) => {
                                return fetch('register.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: 'activation_code=' + code
                                }).then(response => response.text());
                            }
                        }).then(result => {
                            if (result.value.includes('success')) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Başarılı!',
                                    text: 'Kaydınız tamamlandı, giriş yapabilirsiniz.',
                                    confirmButtonText: 'Tamam'
                                }).then(() => {
                                    window.location.href = 'login.php';
                                });
                            } else {
                                attemptCount++; // Yanlış giriş sayısını artır
                                if (attemptCount >= 3) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Hata!',
                                        text: 'Çok fazla hatalı giriş yaptınız.',
                                        confirmButtonText: 'Tamam'
                                    }).then(() => {
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Hata!',
                                        text: 'Aktivasyon kodu yanlış! Lütfen tekrar deneyin.',
                                        confirmButtonText: 'Tekrar Dene'
                                    }).then(() => {
                                        showActivationPopup(); // Tekrar pop-up göster
                                    });
                                }
                            }
                        });
                    }
            
                    showActivationPopup(); // İlk pop-up gösterimi
                });
            </script>";
            } catch (Exception $e) {
                $error = "E-posta gönderilemedi: " . $mail->ErrorInfo;
            }
        }
    }
    
    // Aktivasyon kodu doğrulama
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['activation_code'])) {
        $entered_code = trim($_POST['activation_code']);
    
        if ($entered_code == $_SESSION['activation_code']) {
            $user_data = $_SESSION['user_data'];
    
            // Kullanıcıyı kaydet
            $hashed_password = password_hash($user_data['password'], PASSWORD_BCRYPT);
            $sql_insert = "INSERT INTO users (password_users, mail_users, name_users, surname_users, telefon, birthday_users, sex, isAdmin)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sssssss", $hashed_password, $user_data['email'], $user_data['name'], $user_data['surname'], $user_data['telefon'], $user_data['birthday'], $user_data['sex']);
    
            if ($stmt_insert->execute()) {
                unset($_SESSION['activation_code'], $_SESSION['user_data']);
                echo "success";
            } else {
                echo "error";
            }
            $stmt_insert->close();
        } else {
            echo "error";
        }
    }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="css/login.css?v=1.0">
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
        <form action="register.php" method="post" onsubmit="return validatePassword()">
            <div class="back-button">
                <a href="login.php"><i class='bx bx-arrow-back' style="color: black;"></i></a>
            </div>
            <div class="logo"><img src="images/logo-has.png" alt="Logo"></div>
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
                <i class='bx bxs-user' style="color:black;"></i>
            </div>
            <div class="input-box">
                <input type="password" id="password" name="password_users" placeholder="Şifre" required>
                <i class='bx bxs-lock-alt' style="color:black"></i>
                <small id="password-error" style="color:red; display:none; margin: 0px 10px;">Şifre, en az 7 karakter ve harf-rakam içermeli.</small>
            </div>
            <div class="input-box">
                <input type="text" name="name_users" id="name_users" placeholder="Adınız" required>
                <i class='bx bxs-user' style="color:black;"></i>
            </div>
            <div class="input-box">
                <input type="text" name="surname_users" id="surname_users" placeholder="Soyadınız" required>
                <i class='bx bxs-user' style="color:black;"></i>
            </div>
            <div class="input-box">
                <input type="tel" id="telefon" name="telefon" placeholder="Telefon" required>
                <i class='bx bxs-phone' style="color:black;"></i>
            </div>
            <div class="input-box">
                <input type="date" name="birthday_users" placeholder="Doğum Tarihi" required>
                <i class='bx bxs-calendar' style="color:black;"></i>
            </div>
                    <div class="input-box">
            <label>Cinsiyet:</label>
            <div class="gender-options">
                <input type="radio" id="male" name="sex" value="Erkek" required>
                <label for="male">Erkek</label>
                <input type="radio" id="female" name="sex" value="Kadın" required>
                <label for="female">Kadın</label>
            </div>
        </div>
            <div class="mb-3 fade-in">
                <input type="checkbox" id="kvkk" name="kvkk" required>
                <label for="kvkk">
                    <a href="forms/KVKK.pdf" target="_blank">KVKK</a>'yı okudum ve onaylıyorum.
                </label>
            </div>
            <button type="submit" class="btn">Kayıt Ol</button>
        </form>
    </div>

    <!-- jQuery ve intl-tel-input -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"></script>

    <!-- Register.js -->
    <script src="js/register.js"></script>
    <script>
        function validatePassword() {
            const password = document.getElementById('password').value;
            const passwordError = document.getElementById('password-error');
            const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{7,}$/;

            if (!passwordPattern.test(password)) {
                passwordError.style.display = 'block'; // Hata mesajını göster
                return false; // Formun gönderilmesini engeller
            } else {
                passwordError.style.display = 'none'; // Hata mesajını gizler
                return true; // Formun gönderilmesine izin verir
            }
        }
    </script>

</body>
</html>
