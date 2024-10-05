<?php
// Veritabanı bağlantısını yap
include 'dB/database.php';
include 'navbar.php';
include 'bootstrap.php';

// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Haber ID'sini al
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ID'ye göre haber detaylarını al
$newsQuery = "SELECT id, name, summary, text, image_path1, image_path2, image_path3, created_at FROM news WHERE id = ?";
$stmt = $conn->prepare($newsQuery);

if (!$stmt) {
    echo "Sorgu hazırlama hatası: " . $conn->error;
    exit;
}

$stmt->bind_param("i", $newsId);
$stmt->execute();

// Sonuçları elde et
$stmt->store_result(); // Sonuçları sakla
$stmt->bind_result($id, $name, $summary, $text, $image_path1, $image_path2, $image_path3, $created_at);
$stmt->fetch(); // Verileri çek

// Eğer haber bulunamazsa
if (!$id) {
    echo "<p>Haber bulunamadı.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haber Detayları</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/news_details.css"> <!-- CSS dosyasını buraya ekleyin -->
</head>
<body>

<div class="container mt-5">
    <div class="slider-container">
        <div id="newsSlider" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner">
                <?php
                // Slider için resimleri al
                $images = [
                    $image_path1,
                    $image_path2,
                    $image_path3,
                ];

                foreach ($images as $index => $image) {
                    if (!empty($image)) {
                        $activeClass = $index === 0 ? 'active' : '';
                        echo "<div class='carousel-item $activeClass'>
                                <img src='" . htmlspecialchars($image) . "' class='d-block w-100 slider-image' alt='Haber Resmi'>
                              </div>";
                    }
                }
                ?>
            </div>
            <a class="carousel-control-prev" href="#newsSlider" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Önceki</span>
            </a>
            <a class="carousel-control-next" href="#newsSlider" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Sonraki</span>
            </a>
        </div>
    </div>

    <!-- Haber Başlığı ve İçeriği -->
    <h2 class="mt-4"><?php echo htmlspecialchars($name); ?></h2>
    <p class="mt-2 news-text"><?php echo nl2br(htmlspecialchars($text)); ?></p>
    </div>

<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
