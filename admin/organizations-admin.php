<?php
include 'sidebar.php';
include '../db/database.php'; // Veritabanı bağlantısını dahil et
include '../bootstrap.php';

// Kullanıcı giriş kontrolü
$user_id = isset($_SESSION['id_users']) ? $_SESSION['id_users'] : null; // Kullanıcı ID'si

// Filtreleme değişkenleri
$registration_time = isset($_POST['registration_time']) ? $_POST['registration_time'] : (isset($_GET['registration_time']) ? $_GET['registration_time'] : null);
$category = isset($_POST['category']) ? $_POST['category'] : (isset($_GET['category']) ? $_GET['category'] : null);

// Sayfalama ayarları
$items_per_page = 5; // Her sayfada gösterilecek kart sayısı
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Geçerli sayfa numarası
$offset = ($current_page - 1) * $items_per_page; // Offset hesaplama

// Silme işlemi
if (isset($_GET['delete_id'])) {
    $organization_id = intval($_GET['delete_id']);

    // İlk olarak organizasyonun fiyatlarını sil
    $delete_prices_sql = "DELETE FROM prices WHERE organization_id = ?";
    $stmt = $conn->prepare($delete_prices_sql);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();

    // Ardından organizasyonu sil
    $delete_organization_sql = "DELETE FROM organizations WHERE id = ?";
    $stmt = $conn->prepare($delete_organization_sql);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();

    // Başarılı bir şekilde silindiyse
    if ($stmt->affected_rows > 0) {
        // Başarılı silme durumu
        $_SESSION['success_message'] = 'Organizasyon başarıyla silindi.';
    } else {
        $_SESSION['error_message'] = 'Silme işlemi sırasında bir hata oluştu.';
    }

    $stmt->close();
}

// Sayfa yüklendiğinde oturum değişkenini sıfırla
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']); // Değişkenleri sıfırla

// Sorgu başlangıcı
$sql = "
    SELECT o.*, p.downhill_price, p.enduro_price, p.tour_price, p.ulumega_price, p.ebike_price, o.race_details_pdf, o.type
    FROM organizations o 
    LEFT JOIN prices p ON o.id = p.organization_id";

// Filtreleme ekle
$filters = [];

// Kayıt durumunu kontrol et
if ($registration_time === 'past') {
    $filters[] = "o.last_register_day < NOW()";
} elseif ($registration_time === 'ongoing') {
    $filters[] = "o.last_register_day >= NOW() AND o.register_start_date <= NOW()";
} elseif ($registration_time === 'upcoming') {
    $filters[] = "o.register_start_date > NOW()";
}

// Kategori filtrelemesi
if ($category) {
    if ($category === 'downhill') {
        $filters[] = "o.downhill = 1";
    } elseif ($category === 'enduro') {
        $filters[] = "o.enduro = 1";
    } elseif ($category === 'tour') {
        $filters[] = "o.tour = 1";
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
$total_sql = str_replace("o.*, p.downhill_price, p.enduro_price, p.tour_price, p.ulumega_price, p.ebike_price, o.race_details_pdf, o.type", "COUNT(*) as total", $sql);
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_items = $total_row['total']; // Toplam kayıt sayısı
$total_pages = ceil($total_items / $items_per_page); // Toplam sayfa sayısı

// Sorguya limit ekle
$sql .= " LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

$pdf_file_path = '../documents/race_details/'; // PDF dosya yolu
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <link rel="stylesheet" href="admincss/organizations-admin.css"> <!-- CSS dosyasına bağlantı -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"> <!-- SweetAlert2 CSS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script> <!-- SweetAlert2 JS -->
    <title>Organizasyonlar</title>
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center mb-4">Organizasyonlar</h1>

    <!-- SweetAlert2 Mesajları -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($success_message): ?>
                Swal.fire({
                    title: 'Başarılı!',
                    text: '<?= $success_message; ?>',
                    icon: 'success',
                    confirmButtonText: 'Tamam'
                });
            <?php endif; ?>
            <?php if ($error_message): ?>
                Swal.fire({
                    title: 'Hata!',
                    text: '<?= $error_message; ?>',
                    icon: 'error',
                    confirmButtonText: 'Tamam'
                });
            <?php endif; ?>
        });

        function confirmDelete(organizationId) {
            Swal.fire({
                title: 'Emin misiniz?',
                text: 'Bu organizasyonu silmek istediğinize emin misiniz? Organizasyon silindiğinde ona ait kayıtlar ve sonuçlar kalıcı olarak silinecektir !',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, sil!',
                cancelButtonText: 'Hayır, iptal et!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Eğer onaylanırsa, silme işlemi gerçekleştirilecek
                    window.location.href = '?delete_id=' + organizationId;
                }
            });
        }
    </script>

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
                    <option value="tour" <?= $category === 'tour' ? 'selected' : '' ?>>Tour</option>
                    <option value="ulumega" <?= $category === 'ulumega' ? 'selected' : '' ?>>Ulumega</option>
                    <option value="e-bike" <?= $category === 'e-bike' ? 'selected' : '' ?>>E-Bike</option> <!-- E-Bike seçeneği -->
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filtrele</button>
        <a href="add-organizations.php" class="btn btn-secondary mt-3 ms-2">Organizasyon Ekle</a> <!-- Organizasyon Ekle butonu -->
    </form>
    
    <div class="row">
        <?php
        // Eğer sonuç varsa, organizasyonları döngü ile yazdır
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $organization_id = $row['id']; // Organizasyon ID'sini al
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
                if ($row['e_bike']) {
                    $categories[] = 'E-Bike';
                }
                $categories_list = implode(', ', $categories); // Kategorileri birleştir
            
                echo "<div class='col-lg-12 mb-4'>
                            <div class='card organization-card'>
                                <div class='card-content p-4'>
                                    <h5 class='card-title'>{$row['name']}</h5>
                                    <p class='card-text'><strong>Kategoriler:</strong> {$categories_list}</p>
                                    <p class='card-text'><strong>Tür:</strong> {$row['type']}</p>
                                    <p class='card-text'><strong>Kayıt Başlangıç Tarihi:</strong> {$row['register_start_date']}</p>
                                    <p class='card-text'><strong>Son Kayıt Günü:</strong> {$row['last_register_day']}</p>
                                    <p class='card-text'><strong>Detaylar:</strong> {$row['details']}</p>";
            
                // Fiyat bilgilerini ekleyelim
                if (strtotime($row['last_register_day']) >= time()) { 
                    if (!is_null($row['downhill_price'])) {
                        echo "<p class='card-text'><strong>Downhill Kategorisi Yarış Ücreti:</strong> {$row['downhill_price']} TL</p>";
                    }
                    if (!is_null($row['enduro_price'])) {
                        echo "<p class='card-text'><strong>Enduro Kategorisi Yarış Ücreti:</strong> {$row['enduro_price']} TL</p>";
                    }
                    if (!is_null($row['tour_price'])) {
                        echo "<p class='card-text'><strong>Tour Fiyatı:</strong> {$row['tour_price']} TL</p>";
                    }
                    if (!is_null($row['ulumega_price'])) {
                        echo "<p class='card-text'><strong>Ulumega Fiyatı:</strong> {$row['ulumega_price']} TL</p>";
                    }
                    if (!is_null($row['ebike_price'])) {
                        echo "<p class='card-text'><strong>E-Bike Kategorisi Yarış Ücreti:</strong> {$row['ebike_price']} TL</p>";
                    }
                }
            
                // PDF bağlantısını ekleyelim
                echo "<p class='card-text'><strong>Organizasyon detaylarını ve kurallarını indirmek için tıklayınız:</strong> ";
                if (!empty($row['race_details_pdf'])) {
                    echo "<a href='{$pdf_file_path}{$row['race_details_pdf']}' target='_blank' class='btn btn-link'>PDF'i Aç</a>";
                } else {
                    echo "PDF mevcut değil.";
                }
                echo "</p>";
            
                // Düzenle ve Sil butonları
                echo "<div class='mt-3'>
                        <a href='edit-organizations.php?id={$row['id']}' class='btn btn-secondary'>Düzenle</a>
                        <a href='javascript:void(0);' class='btn btn-danger' onclick='confirmDelete({$row['id']});'>Sil</a>
                        <a href='registrationsmanagement.php?organization_id=$organization_id' class='btn btn-secondary' style='text-decoration: none;'>Kayıtları Görüntüle</a>
                      </div>";
            
                echo "  </div>
                        <div class='map-container'>
                            <h6 class='map-title'>Konum</h6>
                            {$iframe}
                        </div>
                      </div>
                    </p>
                  </div>";
            }
        } else {
            echo "<div class='col-12'><p class='text-center'>Kayıt bulunamadı.</p></div>";
        }
        ?>
    </div>

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

</body>
</html>
