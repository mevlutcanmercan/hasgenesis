<?php
// Veritabanı bağlantısını yap
include 'dB/database.php';
include 'navbar.php';
include 'bootstrap.php';

// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Proje ID'sini al
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Proje bilgilerini al
$projectQuery = "SELECT name, text, image_path1, image_path2, image_path3, image_path4, image_path5 FROM projects WHERE id = ?";
$stmt = $conn->prepare($projectQuery);

if (!$stmt) {
    echo "Sorgu hazırlama hatası: " . $conn->error;
    exit;
}

$stmt->bind_param("i", $projectId);
$stmt->execute();

// Sonuçları elde et
$stmt->store_result(); // Sonuçları sakla
$stmt->bind_result($name, $text, $image_path1, $image_path2, $image_path3, $image_path4, $image_path5);
$stmt->fetch(); // Verileri çek

// Eğer proje bulunamazsa hata mesajı göster
if (!$name) {
    echo "<h2>Proje bulunamadı.</h2>";
    exit;
}

// Proje resimlerini bir diziye al
$images = [];
if (!empty($image_path1)) $images[] = $image_path1;
if (!empty($image_path2)) $images[] = $image_path2;
if (!empty($image_path3)) $images[] = $image_path3;
if (!empty($image_path4)) $images[] = $image_path4;
if (!empty($image_path5)) $images[] = $image_path5;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Detayları</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/project_detail.css"> <!-- Özel CSS dosyası -->
</head>
<body>

<div class="container mt-5">
    

    <!-- Carousel -->
    <div id="projectCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php
            $active = 'active';
            foreach ($images as $image): ?>
                <div class="carousel-item <?php echo $active; ?>">
                    <img src="<?php echo htmlspecialchars($image); ?>" class="d-block w-100" alt="Project Image">
                </div>
                <?php $active = ''; // İlk resimden sonra active sınıfını kaldır
            endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#projectCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Önceki</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#projectCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Sonraki</span>
        </button>
    </div>

    <h2 class="text-center mb-4"><?php echo htmlspecialchars($name); ?></h2>

    <!-- Proje Özeti -->
    <div class="project-text mt-4">
        <h4>Proje Detayları</h4>
        <p class="mt-2 news-text"><?php echo nl2br(htmlspecialchars($text)); ?></p>
        </div>
</div>

<!-- Sayfanın en altında olacak footer -->
<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

<script src="js/bootstrap.bundle.js"></script> <!-- Bootstrap JS -->
</body>
</html>
