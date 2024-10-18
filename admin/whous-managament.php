<?php
include 'sidebar.php';
include '../db/database.php'; // Veritabanı bağlantısını dahil et

// Hakkımızda bilgilerini al
$whoUsQuery = "SELECT * FROM who_us WHERE id = 1"; // ID'si 1 olan kaydı al
$result = $conn->query($whoUsQuery);
$whoUs = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen verileri al
    $header = $_POST['header'];
    $text = $_POST['text'];
    
    // Resim yükleme
    $logoPath = $whoUs['logo_path']; // Varsayılan logo yolu
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        // Dosya boyutunu kontrol et (5 MB)
        if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=Dosya%20boyutu%205MB'dan%20büyük%20olamaz!");
            exit();
        }

        // Mevcut logo dosyasını sil
        if (file_exists('../' . $logoPath)) {
            unlink('../' . $logoPath);
        }
        
        // Yeni dosyayı yükle
        $targetDir = '../images/'; // Resimlerin yükleneceği klasör
        $targetFile = $targetDir . basename($_FILES['logo']['name']);
        move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile);
        $logoPath = 'images/' . basename($_FILES['logo']['name']); // Veritabanına kaydedilecek yol
    }

    // Veritabanını güncelle
    $updateQuery = "UPDATE who_us SET header = ?, text = ?, logo_path = ? WHERE id = 1";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('sss', $header, $text, $logoPath);
    $stmt->execute();

    // İşlem başarılıysa sayfayı yeniden yükle
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <title>Hakkımızda Yönetimi</title>
    <link rel="stylesheet" href="admincss/whous-managament.css"> <!-- CSS dosyası -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Dosya boyutu kontrolü
            const fileInput = document.getElementById('logo');
            fileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file && file.size > 5 * 1024 * 1024) { // 5 MB kontrolü
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'Seçtiğiniz dosya boyutu 5MB\'dan büyük olamaz!',
                        confirmButtonText: 'Tamam'
                    });
                    this.value = ''; // Hata durumunda dosya girişini temizle
                }
            });
        });
    </script>
</head>
<body>

<div class="whous-container">
    <h2>Hakkımızda Yönetimi</h2>

    <form method="POST" enctype="multipart/form-data"> <!-- Form veri gönderimi için enctype ayarlandı -->
        <div class="form-group">
            <label for="header">Başlık:</label>
            <input type="text" id="header" name="header" value="<?php echo htmlspecialchars($whoUs['header']); ?>" required>
        </div>

        <div class="form-group">
            <label for="text">Açıklama:</label>
            <textarea id="text" name="text" rows="5" required><?php echo htmlspecialchars($whoUs['text']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="logo">Logo Yükle (Max: 5Mb):</label>
            <input type="file" id="logo" name="logo" accept="image/*">
        </div>

        <div class="form-group">
            <label>Mevcut Logo:</label>
            <img src="<?php echo '../' . htmlspecialchars($whoUs['logo_path']); ?>" alt="Mevcut Logo" />
        </div>

        <button type="submit">Güncelle</button>
    </form>
</div>

</body>
</html>
