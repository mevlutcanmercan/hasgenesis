<?php
include 'dB/database.php'; // Veritabanı bağlantısını buradan alıyoruz
include 'navbar.php';
include 'bootstrap.php';

// Veritabanından bilgileri çek
$query = "SELECT * FROM who_us LIMIT 1";
$result = $conn->query($query); // mysqli bağlantısını kullanıyoruz

$data = []; // Sonuçları tutmak için boş bir dizi oluştur

if ($result) {
    while ($row = $result->fetch_assoc()) { // Her satırı al ve ilişkilendirilmiş diziye ekle
        $data[] = $row;
    }
}
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
    <?php if (!empty($data)): ?>
        <?php foreach ($data as $row): ?>
            <!-- Soldaki resim -->
            <img src="<?php echo htmlspecialchars($row['logo_path']); ?>" alt="Biz Kimiz Logo">
            
            <!-- Sağdaki içerik -->
            <div class="who-us-content">
                <h1><?php echo htmlspecialchars($row['header']); ?></h1>
                <p><?php echo htmlspecialchars($row['text']); ?></p>
                
                <!-- Sosyal medya ikonları -->
                <div class="social-icons">
                    <a href="https://www.facebook.com/hasdownhill" target="_blank"><i class='bx bxl-facebook'></i></a>
                    <a href="https://www.instagram.com/hasdownhill/" target="_blank"><i class='bx bxl-instagram'></i></a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>İçerik bulunamadı.</p> <!-- Varsayılan içerik -->
    <?php endif; ?>
</div>

<script>
// Sayfa yüklendiğinde çalışacak fonksiyon
document.addEventListener('DOMContentLoaded', function() {
    const whoUsContainer = document.querySelector('.who-us-container');

    // Sayfa yüklendiğinde animasyonu başlat
    function showWhoUsContainer() {
        // Eğer who-us-container göründüyse, 'show' sınıfını ekle
        const containerPosition = whoUsContainer.getBoundingClientRect().top;
        const screenPosition = window.innerHeight / 1.3;

        if (containerPosition < screenPosition) {
            whoUsContainer.classList.add('show');
        }
    }

    // Sayfa yüklendiğinde kontrol et
    showWhoUsContainer();

    // Sayfa kaydırıldığında da kontrol et
    window.addEventListener('scroll', showWhoUsContainer);
});
</script>

<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

</body>
</html>
