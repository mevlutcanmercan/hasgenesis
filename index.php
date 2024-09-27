<?php
include 'dB/database.php';
include 'bootstrap.php';

session_start();

$welcomeMessage = "";
$logoutLink = "";
$loginLink = "<a href='/hasgenesis/login' class='btn-login'>Giriş Yap</a>";
 
$profile = "";

if (isset($_SESSION['id_users'])) {
    $userID = $_SESSION['id_users'];

    $userQuery = "SELECT * FROM users WHERE id_users= $userID";
    $userResult = $conn->query($userQuery);

    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $name = $user['name_users']; 
        $welcomeMessage = "<h1 id='hosgeldin' class='welcome-message'>Hoşgeldiniz, " . $name . "</h1>";
    }

    $logoutLink = "<a class='nav-link' href='/hasgenesis/logout.php'>Çıkış Yap</a>";
    $loginLink = ""; 
}


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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light d-flex justify-content-between">
    <div class="container-fluid">
        <a class="navbar-brand header-text" href="#">HAS GENESIS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav mx-auto justify-content-center" id="nav-link-main">
                <a class="nav-link active" aria-current="page" href="#">Ana Sayfa</a>
                <a class="nav-link" href="projeler.php">HAS CREW</a>
                <a class="nav-link" href="Medya.php">PROJELER</a>
                <a class="nav-link" href="hakkimizda.php">HABER BÜLTENİ</a>
                <a class="nav-link" href="iletisim.php">ORGANİZASYONLAR</a>
                <a class="nav-link" href="iletisim.php">BİZ KİMİZ ?</a>
            </div>
            <div class="navbar-nav">                        
            <?php echo $welcomeMessage ; ?>
            <?php echo $loginLink ;  ?>
            </div>
        </div>
    </div>
</nav>
               
<!-- Carousel -->
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
    </button>
</div>
           
<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
