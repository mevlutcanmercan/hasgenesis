<?php
include 'sidebar.php';
include '../db/database.php'; // Veritabanı bağlantısı

// URL'den organizasyon ID'sini al
$organization_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($organization_id > 0) {
    // Organizasyon bilgilerini çek
    $stmt = $conn->prepare("SELECT * FROM organizations WHERE id = ?");
    $stmt->bind_param("i", $organization_id);  // ID'yi sorguya bağla
    $stmt->execute();
    $result = $stmt->get_result(); // Sorgu sonucunu al
    $organization = $result->fetch_assoc(); // Verileri diziye aktar

    // Fiyat bilgilerini çek
    $price_stmt = $conn->prepare("SELECT * FROM prices WHERE organization_id = ?");
    $price_stmt->bind_param("i", $organization_id);
    $price_stmt->execute();
    $price_result = $price_stmt->get_result();
    $prices = $price_result->fetch_assoc();
}

// Form gönderildiğinde (update işlemi)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen veriler
    $organization_name = $_POST['organization_name'];
    $adress = $_POST['adress']; // 'address' yerine 'adress' kullanıldı
    $details = $_POST['details'];
    $register_start_date = $_POST['register_start_date'];
    $last_register_day = $_POST['last_register_day'];
    $organization_type = $_POST['organization_type'];

    // Süspansiyon bilgileri
    $min_front_suspension_travel = !empty($_POST['min_front_suspension_travel']) ? $_POST['min_front_suspension_travel'] : '0';
    $min_rear_suspension_travel = !empty($_POST['min_rear_suspension_travel']) ? $_POST['min_rear_suspension_travel'] : '0';

    // Yarış kategorileri ve fiyatları
    $downhill_price = isset($_POST['downhill']) ? ($_POST['downhill_price'] !== '' ? $_POST['downhill_price'] : '0') : '0';
    $enduro_price = isset($_POST['enduro']) ? ($_POST['enduro_price'] !== '' ? $_POST['enduro_price'] : '0') : '0';
    $ulumega_price = isset($_POST['ulumega']) ? ($_POST['ulumega_price'] !== '' ? $_POST['ulumega_price'] : '0') : '0';
    $hardtail_price = isset($_POST['hardtail']) ? ($_POST['hardtail_price'] !== '' ? $_POST['hardtail_price'] : '0') : '0';
    $ebike_price = isset($_POST['e_bike']) ? ($_POST['e_bike_price'] !== '' ? $_POST['e_bike_price'] : '0') : '0'; // E-Bike fiyatı

    // Yarış Numarası (Bib) ve Özel Yarış Numarası fiyatları
    $bib_price = !empty($_POST['bib_price']) ? $_POST['bib_price'] : '0';
    $special_bib_price = !empty($_POST['special_bib_price']) ? $_POST['special_bib_price'] : '0';

    // PDF dosyası yükleme
    $race_details_pdf = $_FILES['race_details_pdf'];
    $upload_dir = '../documents/race_details/';

    // PDF dosyası yüklendiyse işleme al
    if ($race_details_pdf['type'] == 'application/pdf' && $race_details_pdf['error'] == 0) {
        $pdf_file_name = basename($race_details_pdf['name']);
        $upload_file = $upload_dir . $pdf_file_name;
        move_uploaded_file($race_details_pdf['tmp_name'], $upload_file);
    } else {
        $upload_file = $organization['race_details_pdf']; // Var olan PDF dosyasını koru
    }

    // Organizasyonu güncelle
    $stmt = $conn->prepare("UPDATE organizations SET name=?, adress=?, details=?, register_start_date=?, last_register_day=?, type=?, downhill=?, enduro=?, hardtail=?, ulumega=?, e_bike=?, min_front_suspension_travel=?, min_rear_suspension_travel=?, race_details_pdf=? WHERE id=?");

    $downhill = isset($_POST['downhill']) ? 1 : 0;
    $enduro = isset($_POST['enduro']) ? 1 : 0;
    $hardtail = isset($_POST['hardtail']) ? 1 : 0;
    $ulumega = isset($_POST['ulumega']) ? 1 : 0;
    $ebike = isset($_POST['e_bike']) ? 1 : 0; // E-Bike durumu

    if ($stmt->execute([$organization_name, $adress, $details, $register_start_date, $last_register_day, $organization_type, $downhill, $enduro, $hardtail, $ulumega, $ebike, $min_front_suspension_travel, $min_rear_suspension_travel, $upload_file, $organization_id])) {
        // Fiyatları güncelle
        $price_stmt = $conn->prepare("UPDATE prices SET downhill_price=?, enduro_price=?, ulumega_price=?, hardtail_price=?, e_bike_price=?, bib_price=?, special_bib_price=? WHERE organization_id=?"); 
        if ($price_stmt->execute([$downhill_price, $enduro_price, $ulumega_price, $hardtail_price, $ebike_price, $bib_price, $special_bib_price, $organization_id])) {
            echo '<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>';
            echo '<script>
                    swal("Başarılı!", "Organizasyon başarıyla güncellendi!", "success")
                    .then((value) => {
                        window.location.href = "organizations-admin.php";
                    });
                  </script>';
        } else {
            echo "<script>alert('Fiyat güncellenirken bir hata oluştu.');</script>";
        }
    } else {
        echo "<script>alert('Organizasyon güncellenirken bir hata oluştu.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <link rel="stylesheet" href="admincss/add-organizations.css">
    <title>Organizasyonu Düzenle</title>
    <script>
        function togglePriceInput(checkboxId, priceInputId) {
            var checkbox = document.getElementById(checkboxId);
            var priceInput = document.getElementById(priceInputId);
            if (checkbox.checked) {
                priceInput.style.display = 'block';
            } else {
                priceInput.style.display = 'none';
                priceInput.querySelector('input').value = ''; // Fiyat kutusunu sıfırla
            }
        }
    </script>
</head>
<body>
<div class="form-container">
    <h2>Organizasyonu Düzenle</h2>
    <form action="" method="post" class="organization-form" enctype="multipart/form-data">
        <!-- Organizasyon Genel Bilgiler -->
        <div class="form-group">
            <label for="organization_name">Organizasyon Adı</label>
            <input type="text" id="organization_name" name="organization_name" value="<?= htmlspecialchars($organization['name'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="form-group">
            <label for="adress">Adres</label>
            <textarea id="adress" name="adress" required><?= htmlspecialchars($organization['adress'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form-group">
            <label for="details">Detaylar</label>
            <textarea id="details" name="details"><?= htmlspecialchars($organization['details'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form-group">
            <label for="register_start_date">Kayıt Başlangıç Tarihi</label>
            <input type="date" id="register_start_date" name="register_start_date" value="<?= $organization['register_start_date'] ?>" required>
        </div>

        <div class="form-group">
            <label for="last_register_day">Son Kayıt Tarihi</label>
            <input type="date" id="last_register_day" name="last_register_day" value="<?= $organization['last_register_day'] ?>" required>
        </div>

        <!-- Organizasyon Türü -->
        <div class="form-group">
            <label for="organization_type">Organizasyon Türü</label>
            <select id="organization_type" name="organization_type" required>
                <option value="" disabled>Seçiniz...</option>
                <option value="Yarış" <?= $organization['type'] == 'Yarış' ? 'selected' : '' ?>>Yarış</option>
                <option value="Keşif" <?= $organization['type'] == 'Keşif' ? 'selected' : '' ?>>Keşif</option>
                <option value="Diğer" <?= $organization['type'] == 'Diğer' ? 'selected' : '' ?>>Diğer</option>
            </select>
        </div>

        <!-- Süspansiyon Bilgileri -->
        <div class="form-group">
            <label for="min_front_suspension_travel">Minimum Ön Süspansiyon Mesafesi (mm) - Kural yok ise "0" giriniz !</label>
            <input type="number" id="min_front_suspension_travel" name="min_front_suspension_travel" value="<?= $organization['min_front_suspension_travel'] ?>">
        </div>
        <div class="form-group">
            <label for="min_rear_suspension_travel">Minimum Arka Süspansiyon Mesafesi (mm) - Kural yok ise "0" giriniz !</label>
            <input type="number" id="min_rear_suspension_travel" name="min_rear_suspension_travel" value="<?= $organization['min_rear_suspension_travel'] ?>">
        </div>

        <!-- Yarış Kategorileri -->
        <h3>Yarış Kategorileri</h3>
        <?php
        // Yarış kategorilerini al
        $categories = [
            'downhill' => 'Downhill',
            'enduro' => 'Enduro',
            'ulumega' => 'Ulumega',
            'hardtail' => 'Hardtail',
            'e_bike' => 'E-Bike'
        ];

        foreach ($categories as $key => $value) {
            // Kategorinin durumunu kontrol et
            $is_enabled = $organization[$key]; // Organizasyondaki bu yarış kategorisinin aktif olup olmadığını al
            $price_key = "{$key}_price"; // Fiyata erişim için anahtar
            if ($is_enabled) {
                echo '<div class="form-group">';
                echo '<label>';
                echo "<input type='checkbox' id='$key' name='$key' value='1' onchange=\"togglePriceInput('$key', '{$key}_price')\" " . ($organization[$key] ? 'checked' : '') . ">";
                echo " $value";
                echo '</label>';
                echo '<input type="number" id="' . $key . '_price" name="' . $key . '_price" placeholder="Fiyat" value="' . ($prices[$price_key] ?? '') . '" style="display: ' . ($organization[$key] ? 'block' : 'none') . ';">';
                echo '</div>';
            }
        }
        ?>

        <!-- PDF Yükle -->
        <div class="form-group">
            <label for="race_details_pdf">Yarış Detayları PDF (isteğe bağlı)</label>
            <input type="file" id="race_details_pdf" name="race_details_pdf" accept="application/pdf">
            <small>Mevcut PDF: <?= htmlspecialchars($organization['race_details_pdf'], ENT_QUOTES, 'UTF-8') ?></small>
        </div>

        <!-- Fiyatlar -->
        <h3>Yarış Numarası Fiyatları</h3>
        <div class="form-group">
            <label for="bib_price">Yarış Numarası (Bib) Fiyatı:</label>
            <input type="number" id="bib_price" name="bib_price" value="<?= $prices['bib_price'] ?? '' ?>">
        </div>
        <div class="form-group">
            <label for="special_bib_price">Özel Yarış Numarası Fiyatı:</label>
            <input type="number" id="special_bib_price" name="special_bib_price" value="<?= $prices['special_bib_price'] ?? '' ?>">
        </div>

        <div class="form-group">
            <button type="submit">Güncelle</button>
        </div>
    </form>
</div>
</body>
</html>
