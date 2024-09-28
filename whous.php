<?php
include 'dB/database.php'; // Veritabanı bağlantısını buradan alıyoruz
include 'navbar.php';
include 'bootstrap.php';


// Veritabanından bilgileri çek
$query = "SELECT * FROM who_us LIMIT 1";
$result = $conn->query($query); // mysqli bağlantısını kullanıyoruz
$data = $result->fetch_all(MYSQLI_ASSOC); // Sonuçları al
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biz Kimiz</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/who-us.css"> <!-- CSS dosyasına bağlantı -->
</head>
<body>

<div class="who-us-container">
    <!-- Veritabanındaki her satırı çek -->
    <?php foreach ($data as $row): ?>
        <!-- Soldaki resim -->
        <img src="<?php echo $row['logo_path']; ?>" alt="Biz Kimiz Logo">
        
        <!-- Sağdaki içerik -->
        <div class="who-us-content">
            <h1><?php echo $row['header']; ?></h1>
            <p><?php echo $row['text']; ?></p>
            
            <!-- Sosyal medya ikonları -->
            <div class="social-icons">
                <a href="#"><i class='bx bxl-facebook'></i></a>
                <a href="#"><i class='bx bxl-twitter'></i></a>
                <a href="#"><i class='bx bxl-instagram'></i></a>
                <a href="#"><i class='bx bxl-linkedin'></i></a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>


</body>
</html>
