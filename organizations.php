<?php
include 'dB/database.php';  // Veritabanı bağlantısı
include 'navbar.php';        // Navigasyon barı
include 'bootstrap.php';     // Bootstrap CSS

// Kullanıcı giriş kontrolü
$user_id = isset($_SESSION['id_users']) ? $_SESSION['id_users'] : null; // Kullanıcı ID'si





// Filtreleme değişkenleri
$registration_time = isset($_POST['registration_time']) ? $_POST['registration_time'] : (isset($_GET['registration_time']) ? $_GET['registration_time'] : null);
$category = isset($_POST['category']) ? $_POST['category'] : (isset($_GET['category']) ? $_GET['category'] : null);

// Sayfalama ayarları
$items_per_page = 5; // Her sayfada gösterilecek kart sayısı
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Geçerli sayfa numarası
$offset = ($current_page - 1) * $items_per_page; // Offset hesaplama

// Sorgu başlangıcı
$sql = "
    SELECT o.*, p.downhill_price, p.enduro_price, p.hardtail_price, p.ulumega_price, p.e_bike_price, o.race_details_pdf, o.type
    FROM organizations o 
    LEFT JOIN prices p ON o.id = p.organization_id"; // E-Bike fiyatı için p.ebike_price eklendi

// Filtreleme ekle
$filters = [];

// Kayıt durumunu kontrol et
if ($registration_time === 'past') {
    // Kayıt süresi geçmiş olan yarışlar
    $filters[] = "o.last_register_day < NOW()";
} elseif ($registration_time === 'ongoing') {
    // Kayıt süresi devam eden yarışlar
    $filters[] = "o.last_register_day >= NOW() AND o.register_start_date <= NOW()";
} elseif ($registration_time === 'upcoming') {
    // Kayıt süresi başlamamış olan yarışlar
    $filters[] = "o.register_start_date > NOW()";
}

// Kategori filtrelemesi
if ($category) {
    if ($category === 'downhill') {
        $filters[] = "o.downhill = 1";
    } elseif ($category === 'enduro') {
        $filters[] = "o.enduro = 1";
    } elseif ($category === 'hardtail') {
        $filters[] = "o.hardtail = 1";
    } elseif ($category === 'ulumega') {
        $filters[] = "o.ulumega = 1";
    } elseif ($category === 'e-bike') { // E-Bike kategorisi kontrolü
        $filters[] = "o.e_bike = 1"; // E-Bike kategorisi için filtre
    }
}

// Filtreleri sorguya ekle
if (count($filters) > 0) {
    $sql .= " WHERE " . implode(' AND ', $filters);
}

// Toplam kayıt sayısını hesapla
$total_sql = str_replace("o.*, p.downhill_price, p.enduro_price, p.hardtail_price, p.ulumega_price, p.e_bike_price, o.race_details_pdf, o.type", "COUNT(*) as total", $sql);
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_items = $total_row['total']; // Toplam kayıt sayısı
$total_pages = ceil($total_items / $items_per_page); // Toplam sayfa sayısı

// Sorguya limit ekle
$sql .= " LIMIT $offset, $items_per_page";
$result = $conn->query($sql);


// Yarış Tipi Seçimi
$race_type = isset($_POST['race_type']) ? $_POST['race_type'] : null;

// Organizasyonları getir
$org_query = "SELECT id, name FROM organizations";
$org_result = $conn->query($org_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1000"> <!-- Duyarlı tasarım için önemli -->
    <title>Organizasyonlar</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/organizations.css"> <!-- Özel CSS dosyanız -->
    <style>
        .disabled-btn {
            opacity: 0.5; /* Soluk görünüm için */
            pointer-events: none; /* Tıklanamaz hale getirme */
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center mb-4">Organizasyonlar</h1>

    <!-- Filtreleme Barı -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="registration_time" class="form-label">Kayıt Zamanı:</label>
                <select name="registration_time" id="registration_time" class="form-select">
                    <option value="">Tüm Zamanlar</option>
                    <option value="past" <?= $registration_time === 'past' ? 'selected' : '' ?>>Kayıt Süresi Geçmiş</option>
                    <option value="ongoing" <?= $registration_time === 'ongoing' ? 'selected' : '' ?>>Kayıt Devam Ediyor</option>
                    <option value="upcoming" <?= $registration_time === 'upcoming' ? 'selected' : '' ?>>Kayıt Başlamadı</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="category" class="form-label">Kategori:</label>
                <select name="category" id="category" class="form-select">
                    <option value="">Tüm Kategoriler</option>
                    <option value="downhill" <?= $category === 'downhill' ? 'selected' : '' ?>>Downhill</option>
                    <option value="enduro" <?= $category === 'enduro' ? 'selected' : '' ?>>Enduro</option>
                    <option value="hardtail" <?= $category === 'hardtail' ? 'selected' : '' ?>>Hardtail</option>
                    <option value="ulumega" <?= $category === 'ulumega' ? 'selected' : '' ?>>Ulumega</option>
                    <option value="e-bike" <?= $category === 'e-bike' ? 'selected' : '' ?>>E-Bike</option> <!-- E-Bike seçeneği -->
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filtrele</button>
    </form>

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
            if ($row['hardtail']) {
                $categories[] = 'Hardtail';
            }
            if ($row['ulumega']) {
                $categories[] = 'Ulumega';
            }
            if ($row['e_bike']) { // E-Bike kategorisi kontrolü
                $categories[] = 'E-Bike'; // E-Bike kategorisini ekle
            }
            $categories_list = implode(', ', $categories); // Kategorileri birleştir

            echo "<div class='col-lg-12 mb-4'>
                    <div class='card organization-card d-flex flex-row flex-wrap'> <!-- Flex-wrap ekledik -->
                        <div class='card-content flex-grow-1 p-4'>
                            <h5 class='card-title'>{$row['name']}</h5>
                            <p class='card-text'><strong>Kategoriler:</strong> {$categories_list}</p>
                            <p class='card-text'><strong>Tür:</strong> {$row['type']}</p> <!-- Tür bilgisi eklendi -->
                            <p class='card-text'><strong>Kayıt Başlangıç Tarihi:</strong> {$row['register_start_date']}</p>
                            <p class='card-text'><strong>Son Kayıt Günü:</strong> {$row['last_register_day']}</p>
                            <p class='card-text details-container'><strong>Detaylar:</strong> " . nl2br(htmlspecialchars($row['details'])) . "</p>";

           // Fiyatları yazdır
            if (strtotime($row['last_register_day']) >= time()) { // Kayıt süresi geçmemişse fiyatları göster
            // Sadece aktif olan kategorilere göre fiyatları yazdır
            if ($row['downhill'] == 1 && !is_null($row['downhill_price'])) {
                echo "<p class='card-text'><strong>Downhill Kategorisi Ücreti:</strong> {$row['downhill_price']} TL</p>";
            }
            if ($row['enduro'] == 1 && !is_null($row['enduro_price'])) {
                echo "<p class='card-text'><strong>Enduro Kategorisi Ücreti:</strong> {$row['enduro_price']} TL</p>";
            }
            if ($row['hardtail'] == 1 && !is_null($row['hardtail_price'])) {
                echo "<p class='card-text'><strong>hardtail Kategorisi Fiyatı:</strong> {$row['hardtail_price']} TL</p>";
            }
            if ($row['ulumega'] == 1 && !is_null($row['ulumega_price'])) {
                echo "<p class='card-text'><strong>Ulumega Kategorisi Fiyatı:</strong> {$row['ulumega_price']} TL</p>";
            }
            if ($row['e_bike'] == 1 && !is_null($row['e_bike_price'])) { // E-Bike fiyatı kontrolü
                echo "<p class='card-text'><strong>E-Bike Kategorisi Fiyatı:</strong> {$row['e_bike_price']} TL</p>"; // E-Bike fiyatını yazdır
            }
        }

            // PDF dosyası var mı kontrol et
            if (!empty($row['race_details_pdf'])) {
                // PDF adını ve yolunu birleştir
                $pdf_file_name = $row['race_details_pdf']; // Veritabanından PDF adı al
                $pdf_file_path = 'documents/race_details/' . $pdf_file_name; // Yol oluştur
                echo "<p class='card-text'><strong>Yarış Detayları ve Kuralları:</strong><a href='{$pdf_file_path}' target='_blank' class='btn btn-link'>PDF'i Aç</a>";
            } else {
                echo "PDF mevcut değil.";
            }
            echo "</p>";

            // Yarış Sonuçları Kontrolü (mevcutsa gösterilecek)
            $race_result_sql = "SELECT COUNT(*) AS result_count FROM race_results WHERE organization_id = {$row['id']}";
            $race_result_result = $conn->query($race_result_sql);
            $race_result_row = $race_result_result->fetch_assoc();
            $has_results = $race_result_row['result_count'] > 0; // Sonuç var mı kontrolü

            // Kayıt Ol/Kayıt Olundu butonu
            $current_time = time(); // Şu anki zaman
            $register_start_time = strtotime($row['register_start_date']); // Kayıt başlangıç zamanı
            $register_end_time = strtotime($row['last_register_day']); // Kayıt bitiş zamanı

            // Kullanıcının kayıt olduğu organizasyonları kontrol et
            $is_registered = false; // Kullanıcının kayıtlı olup olmadığını tutan değişken
            $approval_status = null; // Onay durumunu tutan değişken

            if ($user_id) {
                $registration_sql = "
                    SELECT r.organization_id, r.approval_status
                    FROM user_registrations ur
                    JOIN registrations r ON ur.registration_id = r.id
                    WHERE ur.user_id = $user_id
                ";
                $registration_result = $conn->query($registration_sql);

                // Kayıtlı organizasyonları kontrol et
                while ($registration_row = $registration_result->fetch_assoc()) {
                    if ($registration_row['organization_id'] == $row['id']) {
                        $is_registered = true; // Kullanıcı kayıtlıysa true olarak ayarla
                        $approval_status = $registration_row['approval_status']; // Onay durumunu al
                        break;
                    }
                }
            }

            if ($current_time < $register_start_time) {
                // Kayıt süresi henüz başlamamışsa
                echo "<a href='#' class='btn btn-primary disabled-btn'>Kayıtlar Henüz Başlamamıştır!</a>";
            } elseif ($current_time > $register_end_time) {
                // Kayıt süresi geçmişse, "Kayıt Süresi Bitmiştir!" mesajını göster
                echo "<a href='#' class='btn btn-primary disabled-btn'>Kayıt Süresi Bitmiştir!</a>";
            } elseif ($is_registered) {
                // Eğer kullanıcı kayıtlıysa onay durumuna göre mesaj göster
                if ($approval_status == 0) {
                    echo "<a href='#' class='btn btn-warning disabled-btn'>Kayıt Olundu - Onay Bekleniyor</a>";
                } elseif ($approval_status == 1) {
                    echo "<a href='#' class='btn btn-success disabled-btn'>Kayıt Olundu - Onaylandı</a>";
                }
            } else {
                // Kayıt süresi devam ediyorsa ve kullanıcı kayıtlı değilse "Kayıt Ol" butonunu göster
                if ($user_id) {
                    echo "<a href='registrations?organization_id={$row['id']}' class='btn btn-primary'>Kayıt Ol</a>";
                } else {
                    echo "<a href='login' class='btn btn-primary'>Giriş Yap ve Kayıt Ol</a>";
                }
            }

            // Sonuçları Görüntüle butonunu ekleme
            if ($has_results) {
                echo "<a href='raceresults?organization_id={$row['id']}' class='btn btn-primary' style='margin-left: 10px;'>Sonuçları Görüntüle</a>";
            } else {
                echo "<a href='organization_registers?organization_id={$row['id']}' class='btn btn-secondary' style='margin-left: 10px;'>Kayıtları Görüntüle</a>";
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


    <!-- Sayfalama -->
<!-- Sayfalama -->
<div class="pagination-container">
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="pagination-link" href="?page=<?= $current_page - 1; ?>&registration_time=<?= urlencode($registration_time); ?>&category=<?= urlencode($category); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i === $current_page) ? 'active' : ''; ?>">
                    <a class="pagination-link" href="?page=<?= $i; ?>&registration_time=<?= urlencode($registration_time); ?>&category=<?= urlencode($category); ?>"><?= $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="pagination-link" href="?page=<?= $current_page + 1; ?>&registration_time=<?= urlencode($registration_time); ?>&category=<?= urlencode($category); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

</div>
<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class='text-muted'>HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

</body>
</html>
