<?php
include 'dB/database.php';
include 'bootstrap.php';

// ----------------------------------------------------------------

// Carousel Veritabanından Verilerin Çekilmesi
$sliderQuery = "SELECT * FROM main_page_sliders"; // Tablo adını güncelledik
$sliderResult = $conn->query($sliderQuery);

$carouselItems = "";
$firstItem = true;

if ($sliderResult->num_rows > 0) {
    while($row = $sliderResult->fetch_assoc()) {
        $title = $row['title'];        // Başlık
        $summary = $row['summary'];    // Özet
        $imagePath = $row['image_path']; // Resim yolu
        $link = $row['link'];          // Link

        // Eğer link 'http://' veya 'https://' ile başlamıyorsa, 'https://' ekle
        if (!preg_match('/^(http:\/\/|https:\/\/)/', $link)) {
            $link = 'https://' . $link;
        }

        $activeClass = $firstItem ? 'active' : '';
        $firstItem = false;

        // Her bir carousel-item dinamik olarak oluşturuluyor
        $carouselItems .= "
        <div class='carousel-item $activeClass'>
            <a href='$link'>
                <img src='$imagePath' class='d-block w-100' alt='$title'>  <!-- Resmin yolu kullanılıyor -->
                <div class='carousel-caption d-none d-md-block'>
                    <h2>$title</h2>
                    <p>$summary</p>
                </div>
            </a>
        </div>";
    }
}


// Veritabanından son 4 haberi çekmek için SQL sorgusu
$newsQuery = "SELECT id, name, summary, image_path1 FROM news ORDER BY created_at DESC LIMIT 4";
$newsResult = $conn->query($newsQuery);

$newsCards = "";

if ($newsResult->num_rows > 0) {
    while($row = $newsResult->fetch_assoc()) {
        $newsID = $row['id'];
        $newsName = $row['name'];
        $newsSummary = $row['summary'];
        $imagePath = $row['image_path1'];

        // Her bir haber için kart yapısını oluşturuyoruz
        $newsCards .= "
        <div class='news-card'>
            <img src='$imagePath' alt='$newsName' class='news-card-img'>
            <div class='news-card-body'>
                <h5 class='news-card-title'>$newsName</h5>
                <p class='news-card-summary'>$newsSummary</p>
            </div>
            <div class='news-card-footer'> <!-- Footer kısmı eklendi -->
                <a href='news_details.php?id=$newsID' class='news-card-btn'>Devamını Oku</a>
            </div>
        </div>";

    }
} else {
    // Eğer haber yoksa mesaj göster
    $newsCards = "<p class='no-news-message'>Güncel haber yoktur.</p>";

    
}
?>

<?php include 'navbar.php';?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/index.css?v=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!----------------------------------------------------------------Carousel------------------------------------------------------------------------------------- -->
<div id="mainCarousel" class="carousel slide" data-bs-ride="carousel">
    <!-- Göstergeler -->
    <div class="carousel-indicators">
        <?php
        $sliderCount = $sliderResult->num_rows; // Slider sayısını al
        for ($i = 0; $i < $sliderCount; $i++) {
            $activeClass = $i === 0 ? 'active' : ''; // İlk slayt için active sınıfı ekle
            echo "<button type='button' data-bs-target='#mainCarousel' data-bs-slide-to='$i' class='$activeClass' aria-label='Slide " . ($i + 1) . "'></button>";
        }
        ?>
    </div>

    <div class="carousel-inner">
        <?php echo $carouselItems; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button></div>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------->



<!-- Güncel Haberler Başlığı -->
<div class="container my-5">
    <h2 class="text-center1">Haber Bülteni</h2>

    <!-- Haber Kartları -->
    <div class="news-cards-container">
        <?php echo $newsCards; ?>
    </div>
</div>

<!----------------------------------------------------------------Footer---------------------------------------------------------------------------------------->     
<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>
<!----------------------------------------------------------------Footer---------------------------------------------------------------------------------------->



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
