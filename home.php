<?php
include 'dB/database.php';
include 'navbar.php';
include 'bootstrap.php';

// Carousel Veritabanından Verilerin Çekilmesi
$sliderQuery = "SELECT * FROM main_page_sliders"; // Tablo adını güncelledik
$sliderResult = $conn->query($sliderQuery);

$slides = []; // Slaytları tutacak dizi

if ($sliderResult->num_rows > 0) {
    while($row = $sliderResult->fetch_assoc()) {
        $slides[] = [
            'title' => $row['title'],
            'summary' => $row['summary'],
            'image_path' => $row['image_path'],
            'link' => $row['link']
        ];
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/home.css?v=1.0">
</head>
<body>

<!----------------------------------------------------------------Carousel------------------------------------------------------------------------------------- -->
<div id="mainCarousel" class="carousel slide" data-bs-ride="carousel">
    <!-- Göstergeler -->
    <div class="carousel-indicators">
        <?php foreach ($slides as $index => $slide): ?>
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" aria-label="Slide <?= $index + 1 ?>"></button>
        <?php endforeach; ?>
    </div>

    <div class="carousel-inner">
        <?php foreach ($slides as $index => $slide): ?>
            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>" data-url="<?= $slide['link'] ?>">
                <img src="<?= $slide['image_path'] ?>" class="d-block w-100" alt="<?= htmlspecialchars($slide['title']) ?>">
                <div class="carousel-caption d-md-block">
                    <h2><?= htmlspecialchars($slide['title']) ?></h2>
                    <p><?= htmlspecialchars($slide['summary']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<script>
    // Carousel öğelerine tıklandığında yönlendirme yapılması
    document.querySelectorAll('.carousel-item').forEach(item => {
        item.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            if (url) {
                window.location.href = 'https://' + url; // URL'yi tam olarak değiştirme
            }
        });
    });
</script>
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

</body>
</html>