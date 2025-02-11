<?php
include '../dB/database.php';
include 'sidebar.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer dosyalarını dahil et
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

$alertMessage = '';
$alertType = '';

// Eğer yönlendirme parametresi varsa, mesajı belirle
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $alertMessage = "Haber başarıyla eklendi!";
        $alertType = 'success';
    } else {
        $alertMessage = "Bir hata oluştu!";
        $alertType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $summary = $_POST['summary'];
    $text = $_POST['text'];

    // Resim yollarını saklamak için bir dizi oluştur
    $imagePaths = [];

    // Maksimum dosya boyutu 5MB (byte cinsinden)
    $maxFileSize = 5 * 1024 * 1024;

    // Her bir resmi yükle
    for ($i = 1; $i <= 3; $i++) {
        if (!empty($_FILES['image' . $i]['name'])) {
            $imagePath = $_FILES['image' . $i]['name'];
            $targetDirectory = "../images/"; // Resimlerin yükleneceği klasör
            $targetFile = $targetDirectory . basename($imagePath);
            $imageSize = $_FILES['image' . $i]['size']; // Dosya boyutunu al

            // Eğer dosya boyutu 5 MB'tan büyükse hata mesajı oluştur
            if ($imageSize > $maxFileSize) {
                $alertMessage = "Resim " . $i . " 5 MB'tan büyük olamaz!";
                $alertType = 'error';
                break; // Döngüden çık
            }

            // Resmi yükle
            if (move_uploaded_file($_FILES['image' . $i]['tmp_name'], $targetFile)) {
                $imagePaths[] = 'images/' . basename($imagePath); // Veritabanına kaydedilecek yol
            } else {
                $imagePaths[] = ""; // Yükleme başarısız olursa boş dize
            }
        } else {
            // Resim yüklenmediğinde boş dize olarak ata
            $imagePaths[] = "";
        }
    }

    // Eğer hata mesajı yoksa ve ilk resim vitrin fotoğrafı ise veritabanına ekle
    if ($alertMessage === '') {
        if (empty($imagePaths[0])) {
            $alertMessage = "En az bir vitrin fotoğrafı eklemelisiniz!";
            $alertType = 'error';
        } else {
            // SQL sorgusunu hazırla
            $sql = "INSERT INTO news (name, summary, text, image_path1, image_path2, image_path3) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            // Bind param ile değişkenleri bağla
            $stmt->bind_param("ssssss", $name, $summary, $text, $imagePaths[0], $imagePaths[1], $imagePaths[2]);

            // Sorguyu çalıştır
            if ($stmt->execute()) {
                // Kullanıcıların e-posta adreslerini al
                $userQuery = "SELECT mail_users FROM users WHERE mail_users IS NOT NULL AND mail_users != ''";
                $result = $conn->query($userQuery);

                // PHPMailer yapılandırması
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'hasgenesisduyuru@gmail.com';
                    $mail->Password = 'ufjkdlrfjbbcadwh'; // Google App Password kullanmalısınız!
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Gönderen bilgisi
                    $mail->setFrom('hasgenesisduyuru@gmail.com', 'Has Genesis');
                    $mail->addReplyTo('hasgenesisduyuru@gmail.com', 'Has Genesis Destek');
                    $mail->CharSet = 'UTF-8';

                    // E-posta içeriği
                    $subject = "\"" . $name . "\" başlıklı yeni bir haber yayınlandı!";
                    $body = "<p>Merhaba,</p>";
                    $body .= "<p>Has Genesis'te <strong>\"" . $name . "\"</strong> başlıklı yeni bir haber yayınlandı. Detaylar için tıklayın:</p>";
                    $body .= "<p><a href='https://hasgenesis.com/news'>Buraya tıklayın</a></p>";
                    $body .= "<p>Saygılarımızla,</p>";
                    $body .= "<p>Has Genesis</p>";
                    $body .= "<hr>"; // Görsel ayrım için çizgi
                    $body .= "<p style='color: red; font-weight: bold;'>Bu e-posta otomatik olarak gönderilmiştir, lütfen yanıtlamayınız.</p>";
                    $body .= "<p>Bilgi almak için bizimle <a href='mailto:info@hasgenesis.com'>info@hasgenesis.com</a> adresinden iletişime geçebilirsiniz.</p>";


                    // Kullanıcıların e-postalarına döngü ile mail gönder
                    while ($row = $result->fetch_assoc()) {
                        $mail->clearAddresses(); // Önceki adresleri temizle
                        $mail->addAddress($row['mail_users']);
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $mail->Body = $body;
                        $mail->send();
                    }

                    // Yönlendirme yap
                    header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
                    exit;
                } catch (Exception $e) {
                    error_log("E-posta gönderim hatası: " . $mail->ErrorInfo);
                    $alertMessage = "E-posta gönderimi başarısız oldu!";
                    $alertType = 'error';
                }
            } else {
                $alertMessage = "Hata: " . $conn->error;
                $alertType = 'error';
            }
            $stmt->close();
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <link rel="stylesheet" href="admincss/news-add.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Yeni Haber Ekle</title>
</head>
<body>
    <div class="form-container">
        <div class="back-button">
            <i class='bx bx-arrow-back'></i>
        </div>
        <h1>Yeni Haber Ekle</h1>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Haber Başlığı:</label>
                <input type="text" id="name" name="name" required>
                <span class="char-count" id="name-count">0/55</span>
            </div>
            <div class="form-group">
                <label for="summary">Haber Özeti:</label>
                <textarea id="summary" name="summary" rows="4" required></textarea>
                <span class="char-count" id="summary-count">0/175</span>
            </div>
            <div class="form-group">
                <label for="text">Haber Metni:</label>
                <textarea id="text" name="text" rows="4" required></textarea>
            </div>
            <h3>Fotoğaflar (Max: 5mb)</h3>
            <div class="form-group">
                <label for="image1">Vitrin Fotoğrafı (Zorunlu):</label>
                <input type="file" id="image1" name="image1" accept="image/*" required>
            </div>
            <div class="form-group">
                <label for="image2">Fotoğraf 2:</label>
                <input type="file" id="image2" name="image2" accept="image/*">
            </div>
            <div class="form-group">
                <label for="image3">Fotoğraf 3:</label>
                <input type="file" id="image3" name="image3" accept="image/*">
            </div>
            <button type="submit" class="submit-button">Ekle</button>
        </form>
    </div>

    <script>
    // SweetAlert2 mesajı göster
    <?php if ($alertType === 'success' || $alertType === 'error'): ?>
        Swal.fire({
            title: "<?php echo ($alertType === 'success') ? 'Başarılı!' : 'Hata!'; ?>",
            text: "<?php echo $alertMessage; ?>",
            icon: "<?php echo $alertType; ?>",
            confirmButtonText: 'Tamam'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?php echo $_SERVER['PHP_SELF']; ?>";
            }
        });
    <?php endif; ?>

    // Karakter sayacı ve sınır kontrolü
    const maxNameLength = 55;
    const maxSummaryLength = 175;

    const nameInput = document.getElementById('name');
    const nameCount = document.getElementById('name-count');
    nameInput.addEventListener('input', function() {
        const count = this.value.length;
        nameCount.textContent = `${count}/${maxNameLength}`;
        if (count > maxNameLength) {
            this.value = this.value.substring(0, maxNameLength);
            nameCount.textContent = `${maxNameLength}/${maxNameLength}`;
        }
    });

    const summaryInput = document.getElementById('summary');
    const summaryCount = document.getElementById('summary-count');
    summaryInput.addEventListener('input', function() {
        const count = this.value.length;
        summaryCount.textContent = `${count}/${maxSummaryLength}`;
        if (count > maxSummaryLength) {
            this.value = this.value.substring(0, maxSummaryLength);
            summaryCount.textContent = `${maxSummaryLength}/${maxSummaryLength}`;
        }
    });

    // Geri butonu tıklandığında belirli bir URL'ye yönlendir
    document.querySelector('.back-button').addEventListener('click', function() {
        window.location.href = 'newsManagement'; // Belirtilen URL'ye yönlendirme
    });

    // Resim dosya boyutu kontrolü (JavaScript tarafı)
    const maxFileSize = 5 * 1024 * 1024; // 5 MB
    const imageInputs = document.querySelectorAll('input[type="file"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.size > maxFileSize) {
                Swal.fire({
                    title: "Hata!",
                    text: "Dosya boyutu 5 MB'tan büyük olamaz!",
                    icon: "error",
                    confirmButtonText: 'Tamam'
                });
                this.value = ''; // Dosya seçim alanını temizle
            }
        });
    });
    </script>

</body>
</html>