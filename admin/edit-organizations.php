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
    $organization_name = $_POST['organization_name'];
    $adress = $_POST['adress']; // 'address' yerine 'adress' kullanıldı
    $details = $_POST['details'];
    $register_start_date = $_POST['register_start_date'];
    $last_register_day = $_POST['last_register_day'];
    $organization_type = $_POST['organization_type'];

    // Süspansiyon bilgileri
    $min_front_suspension_travel = !empty($_POST['min_front_suspension_travel']) ? $_POST['min_front_suspension_travel'] : NULL;
    $min_rear_suspension_travel = !empty($_POST['min_rear_suspension_travel']) ? $_POST['min_rear_suspension_travel'] : NULL;

    // Yarış kategorileri ve fiyatları
    $downhill_price = isset($_POST['downhill']) ? ($_POST['downhill_price'] !== '' ? $_POST['downhill_price'] : NULL) : NULL;
    $enduro_price = isset($_POST['enduro']) ? ($_POST['enduro_price'] !== '' ? $_POST['enduro_price'] : NULL) : NULL;
    $ulumega_price = isset($_POST['ulumega']) ? ($_POST['ulumega_price'] !== '' ? $_POST['ulumega_price'] : NULL) : NULL;
    $tour_price = isset($_POST['tour']) ? ($_POST['tour_price'] !== '' ? $_POST['tour_price'] : NULL) : NULL;

    // Yarış Numarası (Bib) ve Özel Yarış Numarası fiyatları
    $bib_price = !empty($_POST['bib_price']) ? $_POST['bib_price'] : NULL; 
    $special_bib_price = !empty($_POST['special_bib_price']) ? $_POST['special_bib_price'] : NULL;

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
    $stmt = $conn->prepare("UPDATE organizations SET name=?, adress=?, details=?, register_start_date=?, last_register_day=?, type=?, downhill=?, enduro=?, tour=?, ulumega=?, min_front_suspension_travel=?, min_rear_suspension_travel=?, race_details_pdf=? WHERE id=?");

    $downhill = isset($_POST['downhill']) ? 1 : 0;
    $enduro = isset($_POST['enduro']) ? 1 : 0;
    $tour = isset($_POST['tour']) ? 1 : 0;
    $ulumega = isset($_POST['ulumega']) ? 1 : 0;

    if ($stmt->execute([$organization_name, $adress, $details, $register_start_date, $last_register_day, $organization_type, $downhill, $enduro, $tour, $ulumega, $min_front_suspension_travel, $min_rear_suspension_travel, $upload_file, $organization_id])) {
        // Fiyatları güncelle
        $price_stmt = $conn->prepare("UPDATE prices SET downhill_price=?, enduro_price=?, ulumega_price=?, tour_price=?, bib_price=?, special_bib_price=? WHERE organization_id=?"); 
        if ($price_stmt->execute([$downhill_price, $enduro_price, $ulumega_price, $tour_price, $bib_price, $special_bib_price, $organization_id])) {
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
            <input type="text" id="organization_name" name="organization_name" value="<?= $organization['name'] ?>" required>
        </div>

        <div class="form-group">
            <label for="adress">Adres</label>
            <textarea id="adress" name="adress" required><?= htmlspecialchars($organization['adress'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form-group">
            <label for="details">Detaylar</label>
            <textarea id="details" name="details"><?= $organization['details'] ?></textarea>
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
                <option value="yarış" <?= $organization['type'] == 'yarış' ? 'selected' : '' ?>>Yarış</option>
                <option value="keşif" <?= $organization['type'] == 'keşif' ? 'selected' : '' ?>>Keşif</option>
                <option value="tur" <?= $organization['type'] == 'tur' ? 'selected' : '' ?>>Tur</option>
                <option value="maraton" <?= $organization['type'] == 'maraton' ? 'selected' : '' ?>>Maraton</option>
                <option value="dağcılık" <?= $organization['type'] == 'dağcılık' ? 'selected' : '' ?>>Dağcılık</option>
                <option value="bisiklet" <?= $organization['type'] == 'bisiklet' ? 'selected' : '' ?>>Bisiklet</option>
                <option value="kayak" <?= $organization['type'] == 'kayak' ? 'selected' : '' ?>>Kayak</option>
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

        <!-- Yarış Kategorileri ve Fiyatlar -->
        <div class="form-group">
            <label>Yarış Kategorileri (Ücretsiz etkinliklerde fiyat girmenize gerek yoktur !)</label>
            <div class="checkbox-group">
                <label><input type="checkbox" id="downhill" name="downhill" <?= $organization['downhill'] ? 'checked' : '' ?> onclick="togglePriceInput('downhill', 'downhill_price_container')"> Downhill Fiyat (TL)</label>
                <div id="downhill_price_container" style="<?= $organization['downhill'] ? 'display:block;' : 'display:none;' ?>">
                    <input type="text" id="downhill_price" name="downhill_price" value="<?= $prices['downhill_price'] ?>" placeholder="Downhill Fiyatı">
                </div>
                
                <label><input type="checkbox" id="enduro" name="enduro" <?= $organization['enduro'] ? 'checked' : '' ?> onclick="togglePriceInput('enduro', 'enduro_price_container')"> Enduro Fiyat (TL)</label>
                <div id="enduro_price_container" style="<?= $organization['enduro'] ? 'display:block;' : 'display:none;' ?>">
                    <input type="text" id="enduro_price" name="enduro_price" value="<?= $prices['enduro_price'] ?>" placeholder="Enduro Fiyatı">
                </div>

                <label><input type="checkbox" id="ulumega" name="ulumega" <?= $organization['ulumega'] ? 'checked' : '' ?> onclick="togglePriceInput('ulumega', 'ulumega_price_container')"> Ulumega Fiyat (TL)</label>
                <div id="ulumega_price_container" style="<?= $organization['ulumega'] ? 'display:block;' : 'display:none;' ?>">
                    <input type="text" id="ulumega_price" name="ulumega_price" value="<?= $prices['ulumega_price'] ?>" placeholder="Ulumega Fiyatı">
                </div>

                <label><input type="checkbox" id="tour" name="tour" <?= $organization['tour'] ? 'checked' : '' ?> onclick="togglePriceInput('tour', 'tour_price_container')"> Tur Fiyat (TL)</label>
                <div id="tour_price_container" style="<?= $organization['tour'] ? 'display:block;' : 'display:none;' ?>">
                    <input type="text" id="tour_price" name="tour_price" value="<?= $prices['tour_price'] ?>" placeholder="Tur Fiyatı">
                </div>
            </div>
        </div>

        <!-- Yarış Numarası ve Özel Yarış Numarası -->
        <div class="form-group">
            <label for="bib_price">Yarış Numarası (Bib) Fiyatı</label>
            <input type="number" id="bib_price" name="bib_price" value="<?= $prices['bib_price'] ?>">
        </div>

        <div class="form-group">
            <label for="special_bib_price">Özel Yarış Numarası Fiyatı</label>
            <input type="number" id="special_bib_price" name="special_bib_price" value="<?= $prices['special_bib_price'] ?>">
        </div>

        <!-- PDF Dosyası Yükleme -->
        <div class="form-group">
            <label for="race_details_pdf">Organizasyon Detayları PDF</label>
            <input type="file" id="race_details_pdf" name="race_details_pdf" accept="application/pdf">
            
            <?php if (!empty($organization['race_details_pdf'])): 
                // Dosya adını alalım
                $pdf_file_name = basename($organization['race_details_pdf']);
                $pdf_file_path = '../documents/race_details/' . $pdf_file_name;
            ?>
                <p>
                    Yüklü PDF Dosyası: 
                    <a href="<?= $pdf_file_path ?>" download="<?= $pdf_file_name ?>">
                        <?= $pdf_file_name ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <button type="submit">Güncelle</button>
        </div>
    </form>
</div>
</body>
</html>
