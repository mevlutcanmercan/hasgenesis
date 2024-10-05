<?php
include '../db/database.php';
include 'sidebar.php';

$alertMessage = '';
$alertType = '';

// Düzenlenmek üzere gelen haberin id'sini al
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Eğer id parametresi yoksa, hata ver
    $alertMessage = 'Haber bulunamadı!';
    $alertType = 'error';
} else {
    $newsId = (int) $_GET['id'];

    // Haber bilgilerini veritabanından al
    $sql = "SELECT * FROM news WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $newsId);
    $stmt->execute();
    $result = $stmt->get_result();
    $news = $result->fetch_assoc();

    if (!$news) {
        $alertMessage = 'Haber bulunamadı!';
        $alertType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $summary = $_POST['summary'];
    $text = $_POST['text'];

    // Resim yollarını saklamak için bir dizi oluştur
    $imagePaths = [
        $news['image_path1'],
        $news['image_path2'],
        $news['image_path3']
    ];

    // Yüklü resimleri güncelle
    for ($i = 1; $i <= 3; $i++) {
        if (!empty($_FILES['image' . $i]['name'])) {
            $imagePath = $_FILES['image' . $i]['name'];
            $targetDirectory = "../images/";
            $targetFile = $targetDirectory . basename($imagePath);

            // Resmi yükle
            if (move_uploaded_file($_FILES['image' . $i]['tmp_name'], $targetFile)) {
                $imagePaths[$i - 1] = 'images/' . basename($imagePath);
            }
        }
    }

    // SQL güncelleme sorgusu
    $sql = "UPDATE news SET name = ?, summary = ?, text = ?, image_path1 = ?, image_path2 = ?, image_path3 = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $name, $summary, $text, $imagePaths[0], $imagePaths[1], $imagePaths[2], $newsId);

    if ($stmt->execute()) {
        $alertMessage = "Haber başarıyla güncellendi!";
        $alertType = 'success';
    } else {
        $alertMessage = "Hata: " . $conn->error;
        $alertType = 'error';
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <link rel="stylesheet" href="admincss/news-add.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet"> <!-- Boxicons CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <title>Yeni Haber Ekle</title>
</head>
<body>
    <div class="form-container">

    <div class="form-container">
                <!-- Geri Butonu -->
                <div class="back-button">
            <i class='bx bx-arrow-back'></i>
        </div>
        <h1>Haber Düzenle</h1>

<form method="POST" action="" enctype="multipart/form-data">
    <div class="form-group">
        <label for="name">Haber Başlığı:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($news['name']); ?>" required>
        <span class="char-count" id="name-count">0/100</span>
    </div>
    <div class="form-group">
        <label for="summary">Haber Özeti:</label>
        <textarea id="summary" name="summary" rows="4" required><?php echo htmlspecialchars($news['summary']); ?></textarea>
        <span class="char-count" id="summary-count">0/200</span>
    </div>
    <div class="form-group">
        <label for="text">Haber Metni:</label>
        <textarea id="text" name="text" rows="4" required><?php echo htmlspecialchars($news['text']); ?></textarea>
    </div>
    <div class="form-group">
        <label for="image1">Karttaki Vitrin Fotoğrafı (Zorunlu):</label>
        <input type="file" id="image1" name="image1" accept="image/*">
        <?php if (!empty($news['image_path1'])): ?>
            <p>Mevcut Fotoğraf: <img src="../<?php echo htmlspecialchars($news['image_path1']); ?>" alt="Haber Resmi" style="max-width: 100px;"></p>
        <?php endif; ?>
    </div>
    <div class="form-group">
        <label for="image2">Fotoğraf 2:</label>
        <input type="file" id="image2" name="image2" accept="image/*">
        <?php if (!empty($news['image_path2'])): ?>
            <p>Mevcut Fotoğraf: <img src="../<?php echo htmlspecialchars($news['image_path2']); ?>" alt="Haber Resmi" style="max-width: 100px;"></p>
        <?php endif; ?>
    </div>
    <div class="form-group">
        <label for="image3">Fotoğraf 3:</label>
        <input type="file" id="image3" name="image3" accept="image/*">
        <?php if (!empty($news['image_path3'])): ?>
            <p>Mevcut Fotoğraf: <img src="../<?php echo htmlspecialchars($news['image_path3']); ?>" alt="Haber Resmi" style="max-width: 100px;"></p>
        <?php endif; ?>
    </div>
    <button type="submit" class="submit-button">Güncelle</button>
</form>

</div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <script>
        // Karakter sayacı ve sınır kontrolü
        const maxNameLength = 50;
        const maxSummaryLength = 200;

        // Proje Adı için karakter sınırı
        const nameInput = document.getElementById('name');
        const nameCount = document.getElementById('name-count');
        nameInput.addEventListener('input', function() {
            const count = this.value.length;
            nameCount.textContent = `${count}/${maxNameLength}`;

            // Karakter sayısı sınırı aşarsa fazla karakterleri kaldır
            if (count > maxNameLength) {
                this.value = this.value.substring(0, maxNameLength);
                nameCount.textContent = `${maxNameLength}/${maxNameLength}`;
            }
        });

        // Proje Özeti için karakter sınırı
        const summaryInput = document.getElementById('summary');
        const summaryCount = document.getElementById('summary-count');
        summaryInput.addEventListener('input', function() {
            const count = this.value.length;
            summaryCount.textContent = `${count}/${maxSummaryLength}`;

            // Karakter sayısı sınırı aşarsa fazla karakterleri kaldır
            if (count > maxSummaryLength) {
                this.value = this.value.substring(0, maxSummaryLength);
                summaryCount.textContent = `${maxSummaryLength}/${maxSummaryLength}`;
            }
        });

        // Geri butonu tıklandığında belirli bir URL'ye yönlendir
        document.querySelector('.back-button').addEventListener('click', function() {
            window.location.href = 'newsManagement'; // Belirtilen URL'ye yönlendirme
        });


        // SweetAlert mesajı göster
        <?php if ($alertType === 'success' || $alertType === 'error'): ?>
            swal({
                title: "<?php echo $alertType === 'success' ? 'Başarılı!' : 'Hata!'; ?>",
                text: "<?php echo $alertMessage; ?>",
                icon: "<?php echo $alertType; ?>",
                buttons: true,
                dangerMode: true,
            }).then((willRedirect) => {
                if (willRedirect) {
                    window.location.href = "<?php echo $_SERVER['PHP_SELF']; ?>";
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
