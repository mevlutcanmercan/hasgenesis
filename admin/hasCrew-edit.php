<?php
include '../db/database.php';
include 'sidebar.php';

$alertMessage = '';
$alertType = '';

// Düzenlenecek ekip üyesi ID'sini URL'den alın
$crewID = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ekip üyesi ID'sine göre mevcut veriyi al
if ($crewID > 0) {
    $sql = "SELECT * FROM has_crew WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $crewID);
    $stmt->execute();
    $result = $stmt->get_result();
    $crew = $result->fetch_assoc();
    $stmt->close();
}

// Ekip üyesi verisi mevcut değilse hata ver ve yönlendir
if (!$crew) {
    header("Location: crew-management.php?status=notfound");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberName = $_POST['memberName'];
    $memberDetail = $_POST['memberDetail'];
    $instagram = $_POST['instagram'];
    $twitter = $_POST['twitter'];
    $youtube = $_POST['youtube'];

    // Resim yollarını saklamak için bir dizi oluştur
    $imagePaths = [
        $crew['sliderImagePath'],
        $crew['detailsImagePath'],
        $crew['detailsImagePath2'],
        $crew['detailsImagePath3'],
        $crew['detailsImagePath4'],
        $crew['detailsImagePath5'],
    ];

    // Her bir resmi yükle
    for ($i = 1; $i <= 6; $i++) {
        if (!empty($_FILES['image' . $i]['name'])) {
            $imagePath = $_FILES['image' . $i]['name'];
            $targetDirectory = "../images/"; // Resimlerin yükleneceği klasör
            $targetFile = $targetDirectory . basename($imagePath);

            // Eski resmi sil
            if (!empty($imagePaths[$i - 1])) {
                $oldFilePath = "../" . $imagePaths[$i - 1];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath); // Eski dosyayı sil
                }
            }

            // Yeni resmi yükle
            if (move_uploaded_file($_FILES['image' . $i]['tmp_name'], $targetFile)) {
                $imagePaths[$i - 1] = 'images/' . basename($imagePath); // Yeni yol
            }
        }
    }

    // İlk resmi zorunlu kontrol et
    if (empty($imagePaths[0])) {
        $alertMessage = "Slider fotoğrafı zorunludur!";
        $alertType = 'error';
    } else {
        // SQL sorgusunu hazırla
        $sql = "UPDATE has_crew SET memberName = ?, memberDetail = ?, sliderImagePath = ?, detailsImagePath = ?, detailsImagePath2 = ?, detailsImagePath3 = ?, detailsImagePath4 = ?, detailsImagePath5 = ?, instagram = ?, twitter = ?, youtube = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        // Bind param ile değişkenleri bağla
        $stmt->bind_param("sssssssssssi", $memberName, $memberDetail, $imagePaths[0], $imagePaths[1], $imagePaths[2], $imagePaths[3], $imagePaths[4], $imagePaths[5], $instagram, $twitter, $youtube, $crewID);

        // Sorguyu çalıştır
        if ($stmt->execute()) {
            $alertMessage = "Ekip üyesi başarıyla güncellendi!";
            $alertType = 'success';
            header("Location: hascrewmanagement");
        } else {
            $alertMessage = "Hata: " . $conn->error;
            $alertType = 'error';
        }

        $stmt->close();
    }
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
    <title>Ekip Üyesi Düzenle</title>
</head>
<body>
    <div class="form-container">
        <div class="back-button">
            <i class='bx bx-arrow-back'></i>
        </div>

        <h1>Ekip Üyesi Düzenle</h1>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="memberName">Ekip Üyesi Adı:</label>
                <input type="text" id="memberName" name="memberName" value="<?php echo htmlspecialchars($crew['memberName']); ?>" required>
            </div>
            <div class="form-group">
                <label for="memberDetail">Ekip Üyesi Detayı:</label>
                <textarea type="text" rows="4" id="memberDetail" name="memberDetail" value="<?php echo htmlspecialchars($crew['memberDetail']); ?>" required></textarea>
            </div>

            <div class="form-group">
                <label for="instagram">Instagram (nickname formatında):</label>
                <input type="text" id="instagram" name="instagram" value="<?php echo htmlspecialchars($crew['instagram']); ?>">
            </div>
            <div class="form-group">
                <label for="twitter">Twitter (nickname formatında):</label>
                <input type="text" id="twitter" name="twitter" value="<?php echo htmlspecialchars($crew['twitter']); ?>">
            </div>
            <div class="form-group">
                <label for="youtube">YouTube (nickname formatında):</label>
                <input type="text" id="youtube" name="youtube" value="<?php echo htmlspecialchars($crew['youtube']); ?>">
            </div>
            <div class="form-group">
                <label for="image1">Slider Fotoğrafı (Zorunlu):</label>
                <input type="file" id="image1" name="image1" accept="image/*" onchange="previewImage(this, 'preview1')">
                <?php if (!empty($crew['sliderImagePath'])): ?>
                    <img id="preview1" src="../<?php echo $crew['sliderImagePath']; ?>" alt="Slider Fotoğrafı" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview1" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image2">Detay Fotoğrafı 1:</label>
                <input type="file" id="image2" name="image2" accept="image/*" onchange="previewImage(this, 'preview2')">
                <?php if (!empty($crew['detailsImagePath'])): ?>
                    <img id="preview2" src="../<?php echo $crew['detailsImagePath']; ?>" alt="Detay Fotoğrafı 1" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview2" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image3">Detay Fotoğrafı 2:</label>
                <input type="file" id="image3" name="image3" accept="image/*" onchange="previewImage(this, 'preview3')">
                <?php if (!empty($crew['detailsImagePath2'])): ?>
                    <img id="preview3" src="../<?php echo $crew['detailsImagePath2']; ?>" alt="Detay Fotoğrafı 2" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview3" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image4">Detay Fotoğrafı 3:</label>
                <input type="file" id="image4" name="image4" accept="image/*" onchange="previewImage(this, 'preview4')">
                <?php if (!empty($crew['detailsImagePath3'])): ?>
                    <img id="preview4" src="../<?php echo $crew['detailsImagePath3']; ?>" alt="Detay Fotoğrafı 3" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview4" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image5">Detay Fotoğrafı 4:</label>
                <input type="file" id="image5" name="image5" accept="image/*" onchange="previewImage(this, 'preview5')">
                <?php if (!empty($crew['detailsImagePath4'])): ?>
                    <img id="preview5" src="../<?php echo $crew['detailsImagePath4']; ?>" alt="Detay Fotoğrafı 4" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview5" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image6">Detay Fotoğrafı 5:</label>
                <input type="file" id="image6" name="image6" accept="image/*" onchange="previewImage(this, 'preview6')">
                <?php if (!empty($crew['detailsImagePath5'])): ?>
                    <img id="preview6" src="../<?php echo $crew['detailsImagePath5']; ?>" alt="Detay Fotoğrafı 5" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview6" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <button type="submit" class="submit-button">Güncelle</button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <script>
        // Geri butonu tıklandığında belirli bir URL'ye yönlendir
        document.querySelector('.back-button').addEventListener('click', function() {
            window.location.href = 'hascrewmanagement'; // Belirtilen URL'ye yönlendirme
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
                    window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?id=' . $crewID; ?>";
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
