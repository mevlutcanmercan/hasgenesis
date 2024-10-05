<?php
include '../db/database.php';
include 'sidebar.php';

$alertMessage = '';
$alertType = '';

// Düzenlenecek proje ID'sini URL'den alın
$projectID = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Proje ID'sine göre mevcut veriyi al
if ($projectID > 0) {
    $sql = "SELECT * FROM projects WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projectID);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $stmt->close();
}

// Proje verisi mevcut değilse hata ver ve yönlendir
if (!$project) {
    header("Location: project-management.php?status=notfound");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $summary = $_POST['summary'];
    $text = $_POST['text'];

    // Resim yollarını saklamak için bir dizi oluştur
    $imagePaths = [
        $project['image_path1'],
        $project['image_path2'],
        $project['image_path3'],
        $project['image_path4'],
        $project['image_path5'],
    ];

    // Her bir resmi yükle
    for ($i = 1; $i <= 5; $i++) {
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

                // JavaScript ile mevcut resim önizlemesini güncelle
                echo "<script>updateImagePreview($i, '../images/" . basename($imagePath) . "');</script>";
            }
        }
    }

    // İlk resmi vitrin fotoğrafı olarak kontrol et
    if (empty($imagePaths[0])) {
        $alertMessage = "En az bir vitrin fotoğrafı eklemelisiniz!";
        $alertType = 'error';
    } else {
        // SQL sorgusunu hazırla
        $sql = "UPDATE projects SET name = ?, summary = ?, text = ?, image_path1 = ?, image_path2 = ?, image_path3 = ?, image_path4 = ?, image_path5 = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        // Bind param ile değişkenleri bağla
        $stmt->bind_param("ssssssssi", $name, $summary, $text, $imagePaths[0], $imagePaths[1], $imagePaths[2], $imagePaths[3], $imagePaths[4], $projectID);

        // Sorguyu çalıştır
        if ($stmt->execute()) {
            $alertMessage = "Proje başarıyla güncellendi!";
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet"> <!-- Boxicons CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <title>Proje Düzenle</title>
</head>
<body>
    <div class="form-container">
        <!-- Geri Butonu -->
        <div class="back-button">
            <i class='bx bx-arrow-back'></i>
        </div>

        <h1>Proje Düzenle</h1>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Proje Adı:</label>
                <input type="text" id="name" name="name" maxlength="100" value="<?php echo htmlspecialchars($project['name']); ?>" required>
                <span class="char-count" id="name-count">0/50</span>
            </div>
            <div class="form-group">
                <label for="summary">Proje Özeti:</label>
                <textarea id="summary" name="summary" rows="4" maxlength="200" required><?php echo htmlspecialchars($project['summary']); ?></textarea>
                <span class="char-count" id="summary-count">0/200</span>
            </div>
            <div class="form-group">
                <label for="text">Proje Metni:</label>
                <textarea id="text" name="text" rows="4" required><?php echo htmlspecialchars($project['text']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="image1">Karttaki Vitrin Fotoğrafı (Zorunlu):</label>
                <input type="file" id="image1" name="image1" accept="image/*" onchange="previewImage(this, 'preview1')">
                <?php if (!empty($project['image_path1'])): ?>
                    <img id="preview1" src="../<?php echo $project['image_path1']; ?>" alt="Vitrin Fotoğrafı" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview1" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image2">Fotoğraf 2:</label>
                <input type="file" id="image2" name="image2" accept="image/*" onchange="previewImage(this, 'preview2')">
                <?php if (!empty($project['image_path2'])): ?>
                    <img id="preview2" src="../<?php echo $project['image_path2']; ?>" alt="Fotoğraf 2" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview2" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image3">Fotoğraf 3:</label>
                <input type="file" id="image3" name="image3" accept="image/*" onchange="previewImage(this, 'preview3')">
                <?php if (!empty($project['image_path3'])): ?>
                    <img id="preview3" src="../<?php echo $project['image_path3']; ?>" alt="Fotoğraf 3" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview3" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image4">Fotoğraf 4:</label>
                <input type="file" id="image4" name="image4" accept="image/*" onchange="previewImage(this, 'preview4')">
                <?php if (!empty($project['image_path4'])): ?>
                    <img id="preview4" src="../<?php echo $project['image_path4']; ?>" alt="Fotoğraf 4" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview4" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image5">Fotoğraf 5:</label>
                <input type="file" id="image5" name="image5" accept="image/*" onchange="previewImage(this, 'preview5')">
                <?php if (!empty($project['image_path5'])): ?>
                    <img id="preview5" src="../<?php echo $project['image_path5']; ?>" alt="Fotoğraf 5" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview5" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <button type="submit" class="submit-button">Güncelle</button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <script>
        // Resim önizleme fonksiyonu
        function previewImage(input, previewID) {
            const preview = document.getElementById(previewID);
            const file = input.files[0];
            if (file) {
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

        // Resmi yükledikten sonra mevcut resim alanını güncelle
        function updateImagePreview(imageIndex, newImagePath) {
            const preview = document.getElementById(`preview${imageIndex}`);
            preview.src = newImagePath;
            preview.style.display = "block";
        }

        // SweetAlert mesajı göster ve sayfa yenile
        <?php if ($alertType === 'success' || $alertType === 'error'): ?>
            swal({
                title: "<?php echo $alertType === 'success' ? 'Başarılı!' : 'Hata!'; ?>",
                text: "<?php echo $alertMessage; ?>",
                icon: "<?php echo $alertType; ?>",
                buttons: true,
            }).then(function() {
                if ("<?php echo $alertType; ?>" === "success") {
                    // Sayfayı yeni bir sekmede aç
                    window.open(window.location.href, '_self'); // Sayfayı yeniden açar
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
