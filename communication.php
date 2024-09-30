<?php
include 'dB/database.php';
include 'navbar.php';
include 'bootstrap.php';

// Oturum kontrolü
$is_logged_in = false;
$user_data = [];
$message_sent = false; // Mesajın başarıyla gönderildiğini kontrol eden değişken

if (isset($_SESSION['id_users'])) {
    $user_id = $_SESSION['id_users'];
    // Kullanıcı bilgilerini veritabanından al
    $stmt = $conn->prepare("SELECT mail_users, name_users, surname_users, telefon FROM users WHERE id_users = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($email, $name, $surname, $telefon);
    $stmt->fetch();
    $stmt->close();

    $user_data = [
        'email' => $email,
        'name' => $name,
        'surname' => $surname,
        'telefon' => $telefon
    ];
    $is_logged_in = true;
}

// Form gönderimi kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $isim = $_POST['isim'];
    $soyisim = $_POST['soyisim'];
    $sirket = $_POST['sirket'] ?? '';  // Şirket alanı artık zorunlu değil
    $konu = $_POST['konu'];
    $telefon = $_POST['telefon'];
    $eposta = $_POST['eposta'];
    $mesaj = $_POST['mesaj'];
    $is_user = $is_logged_in ? 1 : 0;

    // İletişim bilgilerini veritabanına kaydet
    $stmt = $conn->prepare("INSERT INTO communication (name, surname, company, topic, phone_number, mail, text, is_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissi", $isim, $soyisim, $sirket, $konu, $telefon, $eposta, $mesaj, $is_user);
    
    if ($stmt->execute()) {
        $message_sent = true; // Başarıyla kaydedildi
    }
    $stmt->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/communication.css">
</head>
<body>

        <div class="container mt-4">
            <div class="row">
                <!-- Sol taraf: Logo ve Açıklama -->
                <div class="col-md-4">
            <img src="images/logo-has.png" alt="Logo" class="img-fluid mb-3">
            <p>Bizimle iletişime geçmek için formu doldurabilirsiniz. Size en kısa sürede geri dönüş yapacağız.</p>
            
            <!-- Sabit E-posta adresi -->
            <p>E-posta: <strong>info@yourcompany.com</strong></p>
            
            <!-- Sosyal Medya İkonları -->
            <div class="social-icons">
                <a href="https://www.facebook.com/hasdownhill" target="_blank"><i class='bx bxl-facebook'></i></a>
                <a href="https://www.instagram.com/hasdownhill/" target="_blank"><i class='bx bxl-instagram'></i></a>
            </div>
        </div>

    
        <!-- Sağ taraf: İletişim Formu -->
        <div class="col-md-8">
            <form action="" method="post">
                <div class="row mb-3 fade-in">
                    <div class="col">
                        <label for="isim" class="form-label">İsim*</label>
                        <input type="text" class="form-control" id="isim" name="isim" value="<?= $is_logged_in ? $user_data['name'] : '' ?>" <?= $is_logged_in ? 'readonly style="opacity:0.5;"' : 'required' ?>>
                    </div>
                    <div class="col">
                        <label for="soyisim" class="form-label">Soyisim*</label>
                        <input type="text" class="form-control" id="soyisim" name="soyisim" value="<?= $is_logged_in ? $user_data['surname'] : '' ?>" <?= $is_logged_in ? 'readonly style="opacity:0.5;"' : 'required' ?>>
                    </div>
                </div>
                <div class="row mb-3 fade-in">
                    <div class="col">
                        <label for="sirket" class="form-label">Şirketiniz (Eğer Var İse)</label>
                        <input type="text" class="form-control" id="sirket" name="sirket">
                    </div>
                    <div class="col">
                        <label for="konu" class="form-label">Konu*</label>
                        <select class="form-control" id="konu" name="konu" required>
                            <option value="">Lütfen bir konu seçin</option>
                            <option value="Destek">Destek</option>
                            <option value="Satış">Satış</option>
                            <option value="Satış İade">Satış İade</option>
                            <option value="Yarış">Yarış</option>
                            <option value="Genel">Sponsorluk</option>
                            <option value="Hascrew Takım Başvurusu">Hascrew Takım Başvurusu</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3 fade-in">
                    <div class="col">
                        <label for="telefon" class="form-label">Telefon*</label>
                        <input type="text" class="form-control" id="telefon" name="telefon" value="<?= $is_logged_in ? $user_data['telefon'] : '' ?>" <?= $is_logged_in ? 'readonly style="opacity:0.5;"' : 'required' ?>>
                    </div>
                    <div class="col">
                        <label for="eposta" class="form-label">E-posta*</label>
                        <input type="email" class="form-control" id="eposta" name="eposta" value="<?= $is_logged_in ? $user_data['email'] : '' ?>" <?= $is_logged_in ? 'readonly style="opacity:0.5;"' : 'required' ?>>
                    </div>
                </div>
                <div class="mb-3 fade-in">
                    <label for="mesaj" class="form-label">Mesaj*</label>
                    <textarea class="form-control" id="mesaj" name="mesaj" rows="4" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">GÖNDER</button>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert ile başarı mesajı -->
<?php if ($message_sent): ?>
<script>
    Swal.fire({
        title: 'Başarılı!',
        text: 'Mesajınız başarılı bir şekilde gönderilmiştir. En kısa sürede size dönüş sağlanacaktır.',
        icon: 'success',
        showConfirmButton: false,
        timer: 3000,
        willClose: () => {
            // Uyarı kutusu kapandığında sayfayı yeniden yükle
            window.location.href = window.location.href; // Mevcut sayfayı yeniden yükler
        }
    });
</script>
<?php endif; ?>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Sayfa yüklendiğinde içerikleri fade-in efektiyle göster
        const container = document.querySelector('.container');
        container.style.opacity = 0; // Başlangıç opaklığı 0
        container.style.transform = 'translateY(20px)'; // Başlangıçta biraz aşağıda
        setTimeout(() => {
            container.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            container.style.opacity = 1; // Yavaşça görünür hale getir
            container.style.transform = 'translateY(0)'; // Aşağıdan yukarı doğru kaydır
        }, 100); // 100ms gecikme ile
    });
</script>

<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

</body>
</html>

