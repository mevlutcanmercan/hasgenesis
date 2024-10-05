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
        $sql = "DELETE FROM news WHERE id IN ($ids)";
        if ($conn->query($sql) === TRUE) {
            $alertMessage = "Seçili haberler başarıyla silindi!";
            $alertType = 'success';
        } else {
            $alertMessage = "Hata: " . $conn->error;
            $alertType = 'error';
        }
    }
}

// Pagination ayarları
$itemsPerPage = 6; // Sayfa başına gösterilecek haber sayısı
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $itemsPerPage;

// Toplam haber sayısını al
$totalItemsQuery = "SELECT COUNT(*) AS total FROM news";
$totalItemsResult = $conn->query($totalItemsQuery);
$totalItems = $totalItemsResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Haberleri veritabanından al
$sql = "SELECT id, name, summary, created_at, image_path1 FROM news ORDER BY created_at DESC LIMIT $itemsPerPage OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <link rel="stylesheet" href="admincss/newsManagement.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <title>Haber Yönetimi</title>
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
                                        <img src="../<?php echo htmlspecialchars($row['image_path1']); ?>" alt="Haber Resmi">
                                    </div>
                                </div>
                                <div class="medya-details">
                                    <h2><?php echo htmlspecialchars($row['name']); ?></h2>
                                    <p class="news-date">
                                        <?php echo strftime("%d.%m.%Y", strtotime($row['created_at'])); ?>
                                    </p>
                                    <p class="news-summary">
                                        <?php echo htmlspecialchars(substr($row['summary'], 0, 175)) . (strlen($row['summary']) > 175 ? '...' : ''); ?>
                                    </p>
                                    <div class="button-container">
                                        <a href="news-edit.php?id=<?php echo $row['id']; ?>" class="edit-button">Düzenle</a>
                                        <div class="checkbox-container">
                                            <input type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>" id="checkbox-<?php echo $row['id']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Hiç haber bulunamadı.</p>
                <?php endif; ?>
            </div>
            <div class="action-buttons">
                <button type="submit" name="delete" class="delete-button">Sil Seçilenler</button>
                <a href="news-add.php" class="add-button">Yeni Ekle</a>
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
    function toggleCheckbox(cardElement) {
        // Kartın içinde bulunan checkbox'ı bul
        const checkbox = cardElement.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.checked = !checkbox.checked; // Checkbox'ı işaretle veya işaretini kaldır
        }
    }

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
