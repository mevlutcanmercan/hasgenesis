<?php
include '../db/database.php';
include 'sidebar.php';

$alertMessage = '';
$alertType = '';

// Eğer yönlendirme parametresi varsa, mesajı belirle
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $alertMessage = "Proje başarıyla eklendi!";
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

    // Her bir resmi yükle
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_FILES['image' . $i]['name'])) {
            $imagePath = $_FILES['image' . $i]['name'];
            $targetDirectory = "../images/"; // Resimlerin yükleneceği klasör
            $targetFile = $targetDirectory . basename($imagePath);

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

    // İlk resmi vitrin fotoğrafı olarak ayarla
    if (empty($imagePaths[0])) {
        $alertMessage = "En az bir vitrin fotoğrafı eklemelisiniz!";
        $alertType = 'error';
    } else {
        // SQL sorgusunu hazırla
        $sql = "INSERT INTO projects (name, summary, text, image_path1, image_path2, image_path3, image_path4, image_path5) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        // Bind param ile değişkenleri bağla
        $stmt->bind_param("ssssssss", $name, $summary, $text, $imagePaths[0], $imagePaths[1], $imagePaths[2], $imagePaths[3], $imagePaths[4]);

        // Sorguyu çalıştır
        if ($stmt->execute()) {
            // Yönlendirme yap
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit; // Yönlendirme yaptıktan sonra çıkış yap
        } else {
            $alertMessage = "Hata: " . $conn->error;
            $alertType = 'error';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <link rel="stylesheet" href="admincss/project-add.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet"> <!-- Boxicons CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <title>Yeni Proje Ekle</title>
</head>
<body>
    <div class="form-container">
        <!-- Geri Butonu -->
        <div class="back-button">
            <i class='bx bx-arrow-back'></i>
        </div>

        <h1>Yeni Proje Ekle</h1>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Proje Adı:</label>
                <input type="text" id="name" name="name" maxlength="100" required>
                <span class="char-count" id="name-count">0/50</span>
            </div>
            <div class="form-group">
                <label for="summary">Proje Özeti:</label>
                <textarea id="summary" name="summary" rows="4" maxlength="200" required></textarea>
                <span class="char-count" id="summary-count">0/200</span>
            </div>
            <div class="form-group">
                <label for="text">Proje Metni:</label>
                <textarea id="text" name="text" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="image1">Karttaki Vitrin Fotoğrafı (Zorunlu):</label>
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
            <div class="form-group">
                <label for="image4">Fotoğraf 4:</label>
                <input type="file" id="image4" name="image4" accept="image/*">
            </div>
            <div class="form-group">
                <label for="image5">Fotoğraf 5:</label>
                <input type="file" id="image5" name="image5" accept="image/*">
            </div>
            <button type="submit" class="submit-button">Ekle</button>
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
            window.location.href = 'project-managament'; // Belirtilen URL'ye yönlendirme
        });

        // SweetAlert mesajı göster ve yönlendir
        <?php if ($alertType === 'success' || $alertType === 'error'): ?>
            swal({
                title: "<?php echo $alertType === 'success' ? 'Başarılı!' : 'Hata!'; ?>",
                text: "<?php echo $alertMessage; ?>",
                icon: "<?php echo $alertType; ?>",
                buttons: true,
            }).then(() => {
                window.location.href = "project-add.php"; // Sayfayı yenile
            });
        <?php endif; ?>
    </script>
</body>
</html>
