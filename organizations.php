<?php
include 'dB/database.php';
include 'navbar.php';
include 'bootstrap.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizasyonlar</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/organizations.css">
</head>
<body>

<div class="container">
    <div class="coming-soon">
        <h1>Yakında Sizlerle</h1>
        <p>Yeni organizasyonlar hakkında daha fazlasını öğrenmek için bizi takip edin!</p>
    </div>
</div>

<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Yazıyı görünür hale getir
        const comingSoon = document.querySelector('.coming-soon');
        setTimeout(() => {
            comingSoon.classList.add('visible');
        }, 250); // 0.250 saniye bekle ve animasyonu başlat
    });
</script>

</body>
</html>
