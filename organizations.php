<?php
include 'dB/database.php';  // Veritabanı bağlantısı
include 'navbar.php';        // Navigasyon barı
include 'bootstrap.php';     // Bootstrap CSS

// Kullanıcı giriş kontrolü
$user_id = isset($_SESSION['id_users']) ? $_SESSION['id_users'] : null; // Kullanıcı ID'si

// Veritabanı sorgusu, organizasyonlar ve fiyatlar tablosunu birleştiriyoruz
$sql = "
    SELECT o.*, p.downhill_price, p.enduro_price, p.tour_price, p.ulumega_price 
    FROM organizations o 
    LEFT JOIN prices p ON o.id = p.organization_id 
    ORDER BY o.register_start_date DESC"; // En son eklenenler
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizasyonlar</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/organizations.css"> <!-- Özel CSS dosyanız -->
    
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center mb-4">Organizasyonlar</h1>
    <div class="row">
        <?php
        // Eğer sonuç varsa, organizasyonları döngü ile yazdır
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Adres sütunundaki iframe kodu
                $iframe = $row['adress']; // Adres (iframe) sütunu

                // Kategorileri kontrol et
                $categories = [];
                if ($row['downhill']) {
                    $categories[] = 'Downhill';
                }
                if ($row['enduro']) {
                    $categories[] = 'Enduro';
                }
                if ($row['tour']) {
                    $categories[] = 'Tour';
                }
                if ($row['ulumega']) {
                    $categories[] = 'Ulumega';
                }
                $categories_list = implode(', ', $categories); // Kategorileri birleştir

                echo "<div class='col-lg-12 mb-4'>
                        <div class='card organization-card d-flex flex-row'>
                            <div class='card-content flex-grow-1 p-4'>
                                <h5 class='card-title'>{$row['name']}</h5>
                                <p class='card-text'><strong>Kategoriler:</strong> {$categories_list}</p>
                                <p class='card-text'><strong>Kayıt Başlangıç Tarihi:</strong> {$row['register_start_date']}</p>
                                <p class='card-text'><strong>Son Kayıt Günü:</strong> {$row['last_register_day']}</p>
                                <p class='card-text'><strong>Detaylar:</strong> {$row['details']}</p>";

                // Fiyatları yazdır
                if (!is_null($row['downhill_price'])) {
                    echo "<p class='card-text'><strong>Downhill Fiyatı:</strong> {$row['downhill_price']} TL</p>";
                }
                if (!is_null($row['enduro_price'])) {
                    echo "<p class='card-text'><strong>Enduro Fiyatı:</strong> {$row['enduro_price']} TL</p>";
                }
                if (!is_null($row['tour_price'])) {
                    echo "<p class='card-text'><strong>Tour Fiyatı:</strong> {$row['tour_price']} TL</p>";
                }
                if (!is_null($row['ulumega_price'])) {
                    echo "<p class='card-text'><strong>Ulumega Fiyatı:</strong> {$row['ulumega_price']} TL</p>";
                }

                // Kayıt Ol butonu
                if ($user_id) {
                    // Kullanıcı giriş yapmışsa, kayıt ol butonunu göster
                    echo "<a href='registration.php?id={$row['id']}' class='btn btn-primary'>Kayıt Ol</a>";
                } else {
                    // Kullanıcı giriş yapmamışsa, login sayfasına yönlendir
                    echo "<a href='login.php' class='btn btn-primary'>Giriş Yap ve Kayıt Ol</a>";
                }

                echo "      </div>
                            <div class='map-container'>
                                <h6 class='map-title'>Konum</h6> <!-- Konum başlığı -->
                                {$iframe} <!-- Harita iframe burada gösteriliyor -->
                            </div>
                        </div>
                      </div>";
            }
        } else {
            echo "<div class='col-12'><p class='text-center'>Kayıt bulunamadı.</p></div>";
        }
        ?>
    </div>
</div>

<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class='text-muted'>HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

</body>
</html>
