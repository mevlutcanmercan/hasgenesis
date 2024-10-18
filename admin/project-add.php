<?php
include '../db/database.php';
include 'sidebar.php';

$alertMessage = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $summary = $_POST['summary'];
    $text = $_POST['text'];

    // Resim yollarını saklamak için bir dizi oluştur
    $imagePaths = [];

    // Hata kontrolü
    $fileSizeError = false;

    // Her bir resmi yükle
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_FILES['image' . $i]['name'])) {
            $imagePath = $_FILES['image' . $i]['name'];
            $targetDirectory = "../images/"; // Resimlerin yükleneceği klasör
            $targetFile = $targetDirectory . basename($imagePath);

            // Dosya boyutunu kontrol et (5MB)
            $maxFileSize = 5 * 1024 * 1024; // 5 MB
            if ($_FILES['image' . $i]['size'] > $maxFileSize) {
                $alertMessage = "Dosya boyutu 5 MB'dan büyük olamaz! Lütfen başka bir dosya seçin.";
                $alertType = 'error';
                $fileSizeError = true; // Hata durumunda işaretle
                break; // Hata durumunda döngüden çık
            }

            // Yeni resmi yükle
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

    // İlk resmi vitrin fotoğrafı olarak kontrol et
    if (empty($imagePaths[0])) {
        $alertMessage = "En az bir vitrin fotoğrafı eklemelisiniz!";
        $alertType = 'error';
    } elseif ($alertType !== 'error' && !$fileSizeError) {
        // SQL sorgusunu hazırla
        $sql = "INSERT INTO projects (name, summary, text, image_path1, image_path2, image_path3, image_path4, image_path5) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        // Bind param ile değişkenleri bağla
        $stmt->bind_param("ssssssss", $name, $summary, $text, $imagePaths[0], $imagePaths[1], $imagePaths[2], $imagePaths[3], $imagePaths[4]);

        // Sorguyu çalıştır
        if ($stmt->execute()) {
            $alertMessage = "Proje başarıyla eklendi!";
            $alertType = 'success';
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    <title>Yeni Proje Ekle</title>
</head>
<body>
    <div class="form-container">
        <div class="back-button">
            <i class='bx bx-arrow-back'></i>
        </div>

        <h1>Yeni Proje Ekle</h1>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Proje Adı:</label>
                <input type="text" id="name" name="name" maxlength="100" required>
                <span class="char-count" id="name-count">0/100</span>
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
            <h3>Resimler (Max: 5mb)</h3>
            <div class="form-group">
                <label for="image1">Karttaki Vitrin Fotoğrafı (Zorunlu):</label>
                <input type="file" id="image1" name="image1" accept="image/*" onchange="previewImage(this, 'preview1')">
                <img id="preview1" style="max-width: 100px; display: none;">
            </div>
            <div class="form-group">
                <label for="image2">Fotoğraf 2:</label>
                <input type="file" id="image2" name="image2" accept="image/*" onchange="previewImage(this, 'preview2')">
                <img id="preview2" style="max-width: 100px; display: none;">
            </div>
            <div class="form-group">
                <label for="image3">Fotoğraf 3:</label>
                <input type="file" id="image3" name="image3" accept="image/*" onchange="previewImage(this, 'preview3')">
                <img id="preview3" style="max-width: 100px; display: none;">
            </div>
            <div class="form-group">
                <label for="image4">Fotoğraf 4:</label>
                <input type="file" id="image4" name="image4" accept="image/*" onchange="previewImage(this, 'preview4')">
                <img id="preview4" style="max-width: 100px; display: none;">
            </div>
            <div class="form-group">
                <label for="image5">Fotoğraf 5:</label>
                <input type="file" id="image5" name="image5" accept="image/*" onchange="previewImage(this, 'preview5')">
                <img id="preview5" style="max-width: 100px; display: none;">
            </div>
            <button type="submit" class="submit-button">Ekle</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const maxNameLength = 100;
        const maxSummaryLength = 200;

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

        document.querySelector('.back-button').addEventListener('click', function() {
            window.location.href = 'project-managament.php';
        });

        function previewImage(input, previewID) {
            const preview = document.getElementById(previewID);
            const file = input.files[0];

            if (file) {
                const maxFileSize = 5 * 1024 * 1024; // 5 MB
                if (file.size > maxFileSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'Dosya boyutu 5 MB\'dan büyük olamaz! Lütfen başka bir dosya seçin.',
                    });
                    input.value = ''; // Hatalı dosyayı temizle
                    preview.style.display = "none"; // Önizlemeyi gizle
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = "block";
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = "none";
            }
        }

        // SweetAlert mesajı göster ve sayfayı yönlendir
        <?php if ($alertType === 'success' || $alertType === 'error'): ?>
            Swal.fire({
                title: "<?php echo $alertType === 'success' ? 'Başarılı!' : 'Hata!'; ?>",
                text: "<?php echo $alertMessage; ?>",
                icon: "<?php echo $alertType; ?>",
                showCancelButton: false,
                confirmButtonText: 'Tamam'
            }).then((result) => {
                if (result.isConfirmed && "<?php echo $alertType; ?>" === "success") {
                    window.location.href = 'project-managament.php'; // Başarı durumunda yönlendir
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
