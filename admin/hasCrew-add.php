<?php
include '../db/database.php';
include 'sidebar.php';

$alertMessage = '';
$alertType = '';
$isSuccess = false; // Başarılı olup olmadığını izlemek için değişken ekliyoruz

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberName = $_POST['memberName'];
    $memberDetail = $_POST['memberDetail'];
    $instagram = $_POST['instagram'];
    $twitter = $_POST['twitter'];
    $youtube = $_POST['youtube'];

    // Resim yollarını saklamak için bir dizi oluştur
    $imagePaths = [];

    // Resimleri yükle ve zorunlu olup olmadıklarına göre işlem yap
    for ($i = 1; $i <= 6; $i++) {
        if (!empty($_FILES['image' . $i]['name'])) {
            $imagePath = $_FILES['image' . $i]['name'];
            $targetDirectory = "../images/"; // Resimlerin yükleneceği klasör
            $targetFile = $targetDirectory . basename($imagePath);

            if (move_uploaded_file($_FILES['image' . $i]['tmp_name'], $targetFile)) {
                $imagePaths[] = 'images/' . basename($imagePath); // Yolu diziye ekle
            } else {
                $imagePaths[] = null; // Hata olursa NULL ekle
            }
        } else {
            $imagePaths[] = null; // Boşsa NULL ekle
        }
    }

    // İlk resmi slider fotoğrafı olarak kontrol et
    if (empty($imagePaths[0])) {
        $alertMessage = "En az bir slider fotoğrafı eklemelisiniz!";
        $alertType = 'error';
    } else {
        // SQL sorgusunu hazırla
        $sql = "INSERT INTO has_crew (memberName, memberDetail, sliderImagePath, detailsImagePath, detailsImagePath2, detailsImagePath3, detailsImagePath4, detailsImagePath5, instagram, twitter, youtube) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        // Bind param ile değişkenleri bağla
        $stmt->bind_param("sssssssssss", $memberName, $memberDetail, $imagePaths[0], $imagePaths[1], $imagePaths[2], $imagePaths[3], $imagePaths[4], $imagePaths[5], $instagram, $twitter, $youtube);

        // Sorguyu çalıştır
        if ($stmt->execute()) {
            $isSuccess = true; // Başarı durumunu değiştir
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
    <title>Yeni Üye Ekle</title>
</head>
<body>
    <div class="form-container">
        <div class="back-button">
            <i class='bx bx-arrow-back'></i>
        </div>

        <h1>Yeni Üye Ekle</h1>

        <form id="memberForm" method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="memberName">Üye Adı:</label>
                <input type="text" id="memberName" name="memberName" maxlength="100" required>
            </div>
            <div class="form-group">
                <label for="memberDetail">Üye Detayları:</label>
                <textarea id="memberDetail" name="memberDetail" rows="4" required></textarea>
            </div>
            
            <!-- Sosyal Medya Alanları -->
            <div class="form-group">
                <label for="instagram">Instagram (nickname formatında):</label>
                <input type="text" id="instagram" name="instagram">
            </div>
            <div class="form-group">
                <label for="twitter">Twitter (nickname formatında):</label>
                <input type="text" id="twitter" name="twitter">
            </div>
            <div class="form-group">
                <label for="youtube">YouTube (nickname formatında):</label>
                <input type="text" id="youtube" name="youtube">
            </div>

            <!-- Resim Yükleme Alanları -->
            <?php for ($i = 1; $i <= 6; $i++): ?>
            <div class="form-group">
                <label for="image<?php echo $i; ?>">Resim <?php echo $i; ?><?php echo $i === 1 ? ' (Slider Resmi Zorunlu)' : ''; ?>:</label>
                <input type="file" id="image<?php echo $i; ?>" name="image<?php echo $i; ?>" accept="image/*" onchange="previewImage(this, 'preview<?php echo $i; ?>'); validateFileSize(this)">
                <img id="preview<?php echo $i; ?>" style="max-width: 100px; display: none;">
            </div>
            <?php endfor; ?>
            
            <button type="submit" class="submit-button">Ekle</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function previewImage(input, previewID) {
        const preview = document.getElementById(previewID);
        const file = input.files[0];
        if (file) {
            // Dosya boyutu kontrolü
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                Swal.fire({
                    title: 'Hata!',
                    text: 'Yüklenen dosya 5MB\'tan büyük olamaz!',
                    icon: 'error',
                    confirmButtonText: 'Tamam',
                }).then(() => {
                    input.value = ""; // Hatalı dosyayı sıfırla
                    preview.style.display = "none"; // Önizlemeyi gizle
                });
                return; // Fonksiyonu sonlandır
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

    document.querySelector('.back-button').addEventListener('click', function() {
        window.location.href = 'hasCrewManagement.php';
    });

    document.getElementById('memberForm').addEventListener('submit', function(event) {
        const sliderImageInput = document.getElementById('image1');
        if (!sliderImageInput.files.length) {
            event.preventDefault(); // Formu gönderme
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'En az bir slider fotoğrafı eklemelisiniz!',
            });
        }
    });

    // PHP'den gelen başarı durumunu kontrol et
    <?php if ($isSuccess): ?>
        Swal.fire({
            icon: 'success',
            title: 'Başarılı!',
            text: 'Yeni üye başarıyla eklendi!',
            confirmButtonText: 'Tamam'
        }).then(() => {
            window.location.href = 'hasCrewManagement.php'; // Başarılı işlemden sonra yönlendir
        });
    <?php endif; ?>
</script>
</body>
</html>
