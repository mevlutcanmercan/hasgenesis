<?php
include '../db/database.php';
include 'sidebar.php';

// Başlangıçta boş olan bildirim mesajları
$alertMessage = '';
$alertType = '';

// Güncellenecek sayfa ID'sini URL'den alın
$pageID = 1; // Örnek ID, bu durumu ihtiyaçlarınıza göre ayarlayın

// Mevcut veriyi al
$query = "SELECT * FROM ulumega_page WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pageID);
$stmt->execute();
$result = $stmt->get_result();
$page = $result->fetch_assoc();
$stmt->close();

// Verisi mevcut değilse yönlendir
if (!$page) {
    header("Location: ulumega-edit.php?status=notfound");
    exit;
}

// Form gönderildiğinde verileri güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $header = $_POST['name'];
    $text = $_POST['summary'];

    // Resim yollarını saklamak için bir dizi oluştur
    $imagePaths = [
        $page['image_path1'],
        $page['image_path2'],
        $page['image_path3'],
    ];

    // Her bir resmi yükle
    for ($i = 1; $i <= 3; $i++) {
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
            if (!move_uploaded_file($_FILES['image' . $i]['tmp_name'], $targetFile)) {
                $alertMessage = "Küçük Resim $i yüklenirken bir hata oluştu!";
                $alertType = 'error';
            } else {
                $imagePaths[$i - 1] = 'images/' . basename($imagePath); // Yeni yol
            }
        }
    }

    // İlk resmi zorunlu kontrol et
    if (empty($imagePaths[0])) {
        $alertMessage = "En az bir küçük resim eklemelisiniz!";
        $alertType = 'error';
    } else {
        // SQL sorgusunu hazırla
        $updateQuery = "UPDATE ulumega_page SET header = ?, text = ?, image_path1 = ?, image_path2 = ?, image_path3 = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);

        // Bind param ile değişkenleri bağla
        $updateStmt->bind_param("sssssi", $header, $text, $imagePaths[0], $imagePaths[1], $imagePaths[2], $pageID);

        // Sorguyu çalıştır
        if ($updateStmt->execute()) {
            $alertMessage = "Veriler başarıyla güncellendi!";
            $alertType = 'success';
        } else {
            $alertMessage = "Hata: " . $conn->error;
            $alertType = 'error';
        }

        $updateStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <link rel="stylesheet" href="admincss/ulumega-edit.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    <title>Ulumega Düzenle</title>
</head>
<body>
    <div class="form-container">
        <div class="back-button">
            <i class='bx bx-arrow-back'></i>
        </div>
        <h1>Ulumega Sayfasını Düzenle</h1>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Başlık</label>
                <input type="text" id="name" name="name" maxlength="55" value="<?php echo htmlspecialchars($page['header']); ?>" required>
                <span class="char-count" id="name-count">0/55</span>
            </div>
            <div class="form-group">
                <label for="summary">Özet:</label>
                <textarea id="summary" name="summary" rows="4" maxlength="800" required><?php echo htmlspecialchars($page['text']); ?></textarea>
                <span class="char-count" id="summary-count">0/800</span>
            </div>
            <h3>Resimler (Max: 5mb)</h3>
            <div class="form-group">
                <label for="image1">Küçük Resim 1 (Zorunlu):</label>
                <input type="file" id="image1" name="image1" accept="image/*" onchange="previewImage(this, 'preview1')">
                <?php if (!empty($page['image_path1'])): ?>
                    <img id="preview1" src="../<?php echo $page['image_path1']; ?>" alt="Küçük Resim 1" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview1" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image2">Küçük Resim 2:</label>
                <input type="file" id="image2" name="image2" accept="image/*" onchange="previewImage(this, 'preview2')">
                <?php if (!empty($page['image_path2'])): ?>
                    <img id="preview2" src="../<?php echo $page['image_path2']; ?>" alt="Küçük Resim 2" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview2" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image3">Küçük Resim 3:</label>
                <input type="file" id="image3" name="image3" accept="image/*" onchange="previewImage(this, 'preview3')">
                <?php if (!empty($page['image_path3'])): ?>
                    <img id="preview3" src="../<?php echo $page['image_path3']; ?>" alt="Küçük Resim 3" style="max-width: 100px;">
                <?php else: ?>
                    <img id="preview3" style="max-width: 100px; display: none;">
                <?php endif; ?>
            </div>
            <button type="submit" class="submit-button">Güncelle</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Karakter sayacı ve sınır kontrolü
        const maxNameLength = 55;
        const maxSummaryLength = 800;

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
            window.location.href = 'adminmainpage.php';
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

        <?php if ($alertType === 'success' || $alertType === 'error'): ?>
            Swal.fire({
                title: "<?php echo $alertType === 'success' ? 'Başarılı!' : 'Hata!'; ?>",
                text: "<?php echo $alertMessage; ?>",
                icon: "<?php echo $alertType; ?>",
                showCancelButton: false,
                confirmButtonText: 'Tamam'
            }).then((result) => {
                if (result.isConfirmed && "<?php echo $alertType; ?>" === "success") {
                    window.location.href = 'ulumega-edit.php'; // Başarı durumunda yönlendir
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
