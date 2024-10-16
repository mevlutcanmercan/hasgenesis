<?php
include '../db/database.php';
include 'sidebar.php';

// Veritabanından mevcut verileri al
$id = 1; // Güncellemek istediğiniz sayfanın ID'si (örnek olarak 1 aldık)

// Mevcut veriyi al
$query = "SELECT * FROM ulumega_page WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$page = $result->fetch_assoc();

// Başarı mesajı için başlangıç değeri
$alertType = '';
$alertMessage = '';

// Form gönderildiğinde verileri güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $header = $_POST['name'];
    $text = $_POST['summary'];

    // Resim yollarını güncelle
    $imagePath1 = $_FILES['image1']['name'] ? 'images/' . $_FILES['image1']['name'] : $page['image_path1'];
    $imagePath2 = $_FILES['image2']['name'] ? 'images/' . $_FILES['image2']['name'] : $page['image_path2'];
    $imagePath3 = $_FILES['image3']['name'] ? 'images/' . $_FILES['image3']['name'] : $page['image_path3'];

    // Resimleri sunucuya yükle
    $uploadSuccess = true; // Yükleme başarılı mı kontrolü
    if ($_FILES['image1']['name']) {
        $uploadSuccess = move_uploaded_file($_FILES['image1']['tmp_name'], '../images/' . $_FILES['image1']['name']) && $uploadSuccess;
    }
    if ($_FILES['image2']['name']) {
        $uploadSuccess = move_uploaded_file($_FILES['image2']['tmp_name'], '../images/' . $_FILES['image2']['name']) && $uploadSuccess;
    }
    if ($_FILES['image3']['name']) {
        $uploadSuccess = move_uploaded_file($_FILES['image3']['tmp_name'], '../images/' . $_FILES['image3']['name']) && $uploadSuccess;
    }

    // Eğer resim yüklemeleri başarılıysa verileri güncelle
    if ($uploadSuccess) {
        $updateQuery = "UPDATE ulumega_page SET header = ?, text = ?, image_path1 = ?, image_path2 = ?, image_path3 = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sssssi", $header, $text, $imagePath1, $imagePath2, $imagePath3, $id);
        $updateSuccess = $updateStmt->execute();

        // Başarı mesajı ayarla
        if ($updateSuccess) {
            $_SESSION['alertType'] = 'success';
            $_SESSION['alertMessage'] = 'Veriler başarıyla güncellendi!';
        } else {
            $_SESSION['alertType'] = 'error';
            $_SESSION['alertMessage'] = 'Güncellenirken bir hata oluştu!';
        }
    } else {
        $_SESSION['alertType'] = 'error';
        $_SESSION['alertMessage'] = 'Resim yüklenirken bir hata oluştu!';
    }

    // Sayfayı yenileyin
    header("Location: " . $_SERVER['PHP_SELF']);
    exit; // Çıkış yaparak kodun devamını çalıştırmayı engelle
}

// SweetAlert mesajını ayarla
$alertType = isset($_SESSION['alertType']) ? $_SESSION['alertType'] : '';
$alertMessage = isset($_SESSION['alertMessage']) ? $_SESSION['alertMessage'] : '';

// Oturum değişkenlerini temizle
unset($_SESSION['alertType']);
unset($_SESSION['alertMessage']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <link rel="stylesheet" href="admincss/ulumega-edit.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.0.0/sweetalert2.min.css">
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
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($page['header']); ?>" required>
                <span class="char-count" id="name-count">0/55</span>
            </div>
            <div class="form-group">
                <label for="summary">Özet:</label>
                <textarea id="summary" name="summary" rows="4" required><?php echo htmlspecialchars($page['text']); ?></textarea>
                <span class="char-count" id="summary-count">0/800</span>
            </div>
            
            <div class="form-group">
                <label for="image1">Küçük Resim 1:</label>
                <input type="file" id="image1" name="image1" accept="image/*">
                <br>
                <img id="preview1" src="../<?php echo htmlspecialchars($page['image_path1']); ?>" alt="Küçük Resim 1" style="max-width: 100px;">
            </div>
            <div class="form-group">
                <label for="image2">Küçük Resim 2:</label>
                <input type="file" id="image2" name="image2" accept="image/*">
                <br>
                <img id="preview2" src="../<?php echo htmlspecialchars($page['image_path2']); ?>" alt="Küçük Resim 2" style="max-width: 100px;">
            </div>
            <div class="form-group">
                <label for="image3">Küçük Resim 3:</label>
                <input type="file" id="image3" name="image3" accept="image/*">
                <br>
                <img id="preview3" src="../<?php echo htmlspecialchars($page['image_path3']); ?>" alt="Küçük Resim 3" style="max-width: 100px;">
            </div>

            <div>
                <label>Arka plan videosu değişikliği için lütfen editörle görüşünüz!</label>
                <br>
            </div>

            <button type="submit" class="submit-button">Güncelle</button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.0.0/sweetalert2.all.min.js"></script>
    <script>
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
            window.location.href = 'newsManagement';
        });

        // Eğer PHP'den başarı durumu gönderildiyse SweetAlert ile mesaj göster
        <?php if ($alertMessage): ?>
            Swal.fire({
                icon: '<?php echo $alertType; ?>',
                title: '<?php echo $alertMessage; ?>',
                showConfirmButton: false,
                timer: 2000 // 2 saniye göster
            }).then(() => {
                location.reload(); // 2 saniye sonra sayfayı yenile
            });
        <?php endif; ?>
    </script>
</body>
</html>
