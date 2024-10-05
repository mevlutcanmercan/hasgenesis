<?php
include '../db/database.php';
include 'sidebar.php';

// Silme işlemi
$alertMessage = '';
$alertType = '';

if (isset($_POST['delete'])) {
    if (!empty($_POST['ids'])) {
        $idsToDelete = $_POST['ids'];
        $ids = implode(',', array_map('intval', $idsToDelete));
        
        // Silinecek kayıtların resim yollarını al
        $sqlSelectImages = "SELECT sliderImagePath, detailsImagePath, detailsImagePath2, detailsImagePath3, detailsImagePath4, detailsImagePath5 FROM has_crew WHERE id IN ($ids)";
        $resultImages = $conn->query($sqlSelectImages);
        
        if ($resultImages->num_rows > 0) {
            while ($rowImages = $resultImages->fetch_assoc()) {
                // Tüm resimleri sil
                $imagePaths = [
                    '../' . $rowImages['sliderImagePath'],
                    '../' . $rowImages['detailsImagePath'],
                    '../' . $rowImages['detailsImagePath2'],
                    '../' . $rowImages['detailsImagePath3'],
                    '../' . $rowImages['detailsImagePath4'],
                    '../' . $rowImages['detailsImagePath5'],
                ];
                
                foreach ($imagePaths as $path) {
                    if (!empty($path) && file_exists($path)) {
                        unlink($path); // Dosyayı sil
                    }
                }
            }
        }

        // Kayıtları sil
        $sqlDelete = "DELETE FROM has_crew WHERE id IN ($ids)";
        if ($conn->query($sqlDelete) === TRUE) {
            $alertMessage = "Seçili ekip üyeleri başarıyla silindi!";
            $alertType = 'success';
        } else {
            $alertMessage = "Hata: " . $conn->error;
            $alertType = 'error';
        }
    }
}

// Pagination ayarları
$itemsPerPage = 6; // Sayfa başına gösterilecek ekip üyesi sayısı
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $itemsPerPage;

// Toplam ekip üyesi sayısını al
$totalItemsQuery = "SELECT COUNT(*) AS total FROM has_crew";
$totalItemsResult = $conn->query($totalItemsQuery);
$totalItems = $totalItemsResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Ekip üyelerini veritabanından al
$sql = "SELECT id, memberName, memberDetail, sliderImagePath, instagram, twitter, youtube FROM has_crew ORDER BY id DESC LIMIT $itemsPerPage OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <link rel="stylesheet" href="admincss/hascrewmanagement.css"> <!-- Mevcut CSS dosyasını kullanabilirsiniz -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <title>Ekip Yönetimi</title>
</head>
<body>
    <div class="medya-content">
        <form method="POST" action="" id="medyaForm">
            <div class="medya-list">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="col-md-4 mb-4 fade-in-card">
                            <div class="medya-card" onclick="toggleCheckbox(this)">
                                <div class="medya-slider">
                                    <div class="medya-images">
                                        <?php if (!empty($row['sliderImagePath'])): ?>
                                            <img src="../<?php echo htmlspecialchars($row['sliderImagePath']); ?>" alt="Ekip Üyesi Resmi">
                                        <?php else: ?>
                                            <img src="../images/default.png" alt="Ekip Üyesi Resmi"> <!-- Varsayılan resim -->
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="medya-details">
                                    <h2><?php echo htmlspecialchars($row['memberName']); ?></h2>
                                    <p class="crew-detail">
                                        <?php echo htmlspecialchars(substr($row['memberDetail'], 0, 175)) . (strlen($row['memberDetail']) > 175 ? '...' : ''); ?>
                                    </p>
                                    <div class="button-container">
                                        <a href="hascrew-edit.php?id=<?php echo $row['id']; ?>" class="edit-button">Düzenle</a>
                                        <div class="checkbox-container">
                                            <input type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>" id="checkbox-<?php echo $row['id']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Hiç ekip üyesi bulunamadı.</p>
                <?php endif; ?>
            </div>
            <div class="action-buttons">
                <button type="submit" name="delete" class="delete-button">Sil Seçilenler</button>
                <a href="hascrew-add.php" class="add-button">Yeni Ekle</a>
            </div>
        </form>
        
        <div class="pagination">
            <?php if ($totalPages > 1): ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Checkbox üzerine tıklama olayını dinle
    document.querySelectorAll('.checkbox-container input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('click', function(event) {
            const cardElement = checkbox.closest('.medya-card');
            toggleCheckbox(cardElement);
            event.stopPropagation(); // Olayı durdur, checkbox'ın kendi işlevi çalışsın
        });
    });

    // SweetAlert ile başarı mesajını göster
    <?php if (!empty($alertMessage)): ?>
        Swal.fire({
            icon: '<?php echo $alertType; ?>',
            title: '<?php echo ucfirst($alertType); ?>',
            text: '<?php echo addslashes($alertMessage); ?>',
            confirmButtonText: 'Tamam'
        }).then(function() {
            window.location.href = window.location.href; // Sayfayı yeniden yükle
        });
    <?php endif; ?>
    </script>
</body>
</html>
