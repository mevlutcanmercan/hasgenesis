<?php
include 'dB/database.php';
include 'bootstrap.php';
include 'navbar.php';

// Veritabanından verileri çekelim
$query = "SELECT * FROM who_us LIMIT 1";
$result = mysqli_query($conn, $query);
$who_us = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biz Kimiz</title>
    <link rel="stylesheet" href="css/who-us.css"> <!-- CSS dosyasına bağlantı -->
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css'> <!-- Boxicons -->
</head>
<body>

<div class="about-us-container">
    <div class="about-us-content">
        <div class="about-us-left">
            <img src="<?php echo $who_us['logo_path']; ?>" alt="Logo" class="about-us-logo">
        </div>
        <div class="about-us-right">
            <h1><?php echo $who_us['header']; ?></h1>
            <p><?php echo $who_us['text']; ?></p>
            <div class="social-icons">
                <a href="https://www.facebook.com/hasdownhill" target="_blank"><i class='bx bxl-facebook'></i></a>
                <a href="https://www.instagram.com/hasdownhill/" target="_blank"><i class='bx bxl-instagram'></i></a>
                <a href="https://www.youtube.com/channel/UC7FjbW-Oi0MG7IDfZRyEBtw" target="_blank"><i class='bx bxl-youtube'></i></a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.querySelector('.about-us-container');
        const content = document.querySelector('.about-us-content');
        
        // Ensure the container is initially hidden
        container.style.opacity = 0; // Başlangıçta görünmez
        container.style.transform = "translateY(20px)"; // Aşağıdan yukarı doğru kayma
        container.style.transition = "opacity 1s ease-out, transform 1s ease-out"; // Geçiş animasyonu

        // Apply a delay to the fade-in effect for the container
        setTimeout(() => {
            container.style.opacity = 1; // Görünür hale getir
            container.style.transform = "translateY(0)"; // Konumunu geri getir
        }, 100); // 100ms delay before starting the fade-in effect

        // Animate the content elements inside the container
        const rows = content.children; // İçerik öğeleri (sol ve sağ içerikler)

        for (let i = 0; i < rows.length; i++) {
            rows[i].style.opacity = 0; // Başlangıçta görünmez
            rows[i].style.transform = "translateY(20px)"; // Aşağıdan yukarı doğru kayma
            rows[i].style.transition = "opacity 1s ease-out, transform 1s ease-out"; // Geçiş animasyonu

            // Apply a delay to each row animation
            setTimeout(() => {
                rows[i].style.opacity = 1; // Görünür hale getir
                rows[i].style.transform = "translateY(0)"; // Konumunu geri getir
            }, 200 * (i + 1)); // Stagger animations by 200ms each
        }
    });
</script>

</body>
</html>
