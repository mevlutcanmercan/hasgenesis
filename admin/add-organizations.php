<?php
include 'sidebar.php';
include '../db/database.php'; // Veritabanı bağlantısı

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $organization_name = $_POST['organization_name'];
    $address = $_POST['address'];
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
    $ebike_price = isset($_POST['e_bike']) ? ($_POST['ebike_price'] !== '' ? $_POST['ebike_price'] : NULL) : NULL; // E-Bike fiyatı

    // Yarış Numarası (Bib) ve Özel Yarış Numarası fiyatları
    $bib_price = !empty($_POST['bib_price']) ? $_POST['bib_price'] : NULL; 
    $special_bib_price = !empty($_POST['special_bib_price']) ? $_POST['special_bib_price'] : NULL; 

    // PDF dosyası yükleme
    $race_details_pdf = $_FILES['race_details_pdf'];
    $upload_dir = '../documents/race_details/'; // admin klasöründen bir üst dizine git

    // Dizin var mı kontrol et, yoksa oluştur
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true); // Dizin oluşturma
    }
    
    // PDF dosyasını kontrol et ve yükle
    if ($race_details_pdf['type'] == 'application/pdf' && $race_details_pdf['error'] == 0) {
        // Dosya adı
        $pdf_file_name = basename($race_details_pdf['name']);
        $upload_file = $upload_dir . $pdf_file_name;
        
        // Dosyayı yükle
        if (move_uploaded_file($race_details_pdf['tmp_name'], $upload_file)) {
            // Dosya başarıyla yüklendi
        } else {
            echo "<script>alert('PDF dosyası yüklenirken bir hata oluştu.');</script>";
            exit; // Yükleme hatası varsa işlem sonlandırılır
        }
    } else {
        echo "<script>alert('Lütfen geçerli bir PDF dosyası yükleyin.');</script>";
        exit; // Geçersiz dosya hatası varsa işlem sonlandırılır
    }

    // Organizasyonu veritabanına ekle
    $stmt = $conn->prepare("INSERT INTO organizations (name, adress, details, register_start_date, last_register_day, type, downhill, enduro, tour, ulumega, e_bike, min_front_suspension_travel, min_rear_suspension_travel, race_details_pdf) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Evet/Hayır değerlerini ayarlama
    $downhill = isset($_POST['downhill']) ? 1 : 0;
    $enduro = isset($_POST['enduro']) ? 1 : 0;
    $tour = isset($_POST['tour']) ? 1 : 0;
    $ulumega = isset($_POST['ulumega']) ? 1 : 0;
    $e_bike = isset($_POST['e_bike']) ? 1 : 0;

    // Sorguyu çalıştır
    if ($stmt->execute([$organization_name, $address, $details, $register_start_date, $last_register_day, $organization_type, $downhill, $enduro, $tour, $ulumega, $e_bike, $min_front_suspension_travel, $min_rear_suspension_travel, $pdf_file_name])) { // Yalnızca dosya adını ekledik
        $organization_id = $conn->insert_id;

        // Fiyatları ekle
        $price_stmt = $conn->prepare("INSERT INTO prices (organization_id, downhill_price, enduro_price, ulumega_price, tour_price, ebike_price, bib_price, special_bib_price) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        if ($price_stmt->execute([$organization_id, $downhill_price, $enduro_price, $ulumega_price, $tour_price, $ebike_price, $bib_price, $special_bib_price])) {
            // Sweet Alert ile başarı mesajı
            echo '<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>';
            echo '<script>
                    swal("Başarılı!", "Organizasyon başarıyla eklendi!", "success")
                    .then((value) => {
                        window.location.href = "organizations-admin.php"; // Burayı yönlendirmek istediğiniz sayfa ile değiştirin
                    });
                  </script>';
        } else {
            echo "<script>alert('Fiyat eklenirken bir hata oluştu: " . $price_stmt->error . "');</script>";
        }
    } else {
        echo "<script>alert('Organizasyon eklenirken bir hata oluştu: " . $stmt->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <link rel="stylesheet" href="admincss/add-organizations.css"> <!-- CSS dosyasına bağlantı -->
    <title>Organizasyon Ekle</title>
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
    <h2>Organizasyon Ekle</h2>
    <form action="" method="post" class="organization-form" enctype="multipart/form-data"> <!-- enctype ekledik -->
        <!-- Organizasyon Genel Bilgiler -->
        <div class="form-group">
            <label for="organization_name">Organizasyon Adı</label>
            <input type="text" id="organization_name" name="organization_name" required>
        </div>

        <div class="form-group">
            <label for="address">Adres</label>
            <input type="text" id="address" name="address" required>
        </div>

        <div class="form-group">
            <label for="details">Detaylar</label>
            <textarea id="details" name="details"></textarea>
        </div>

        <div class="form-group">
            <label for="register_start_date">Kayıt Başlangıç Tarihi</label>
            <input type="date" id="register_start_date" name="register_start_date" required>
        </div>

        <div class="form-group">
            <label for="last_register_day">Son Kayıt Tarihi</label>
            <input type="date" id="last_register_day" name="last_register_day" required>
        </div>

        <!-- Organizasyon Türü -->
        <div class="form-group">
            <label for="organization_type">Organizasyon Türü</label>
            <select id="organization_type" name="organization_type" required>
                <option value="" disabled selected>Seçiniz...</option>
                <option value="yarış">Yarış</option>
                <option value="keşif">Keşif</option>
                <option value="tur">Tur</option>
                <option value="maraton">Maraton</option>
                <option value="dağcılık">Dağcılık</option>
                <option value="bisiklet">Bisiklet</option>
                <option value="kayak">Kayak</option>
            </select>
        </div>

        <!-- Süspansiyon Kuralları -->
        <div class="form-group">
            <label for="min_front_suspension_travel">Minimum Ön Süspansiyon Mesafesi (mm) - Kural yok ise "0" giriniz !</label>
            <input type="number" id="min_front_suspension_travel" name="min_front_suspension_travel" min="0" step="0.1">
        </div>

        <div class="form-group">
            <label for="min_rear_suspension_travel">Minimum Arka Süspansiyon Mesafesi (mm) - Kural yok ise "0" giriniz !</label>
            <input type="number" id="min_rear_suspension_travel" name="min_rear_suspension_travel" min="0" step="0.1">
        </div>

        <!-- Fiyat Kategorileri -->
        <h3>Fiyat Kategorileri</h3>
        <div class="form-group">
            <input type="checkbox" id="downhill" name="downhill" onclick="togglePriceInput('downhill', 'downhill_price_input')">
            <label for="downhill">Downhill</label>
            <div id="downhill_price_input" style="display:none;">
                <label for="downhill_price">Downhill Fiyatı</label>
                <input type="number" id="downhill_price" name="downhill_price" step="0.01">
            </div>
        </div>

        <div class="form-group">
            <input type="checkbox" id="enduro" name="enduro" onclick="togglePriceInput('enduro', 'enduro_price_input')">
            <label for="enduro">Enduro</label>
            <div id="enduro_price_input" style="display:none;">
                <label for="enduro_price">Enduro Fiyatı</label>
                <input type="number" id="enduro_price" name="enduro_price" step="0.01">
            </div>
        </div>

        <div class="form-group">
            <input type="checkbox" id="ulumega" name="ulumega" onclick="togglePriceInput('ulumega', 'ulumega_price_input')">
            <label for="ulumega">Ulumega</label>
            <div id="ulumega_price_input" style="display:none;">
                <label for="ulumega_price">Ulumega Fiyatı</label>
                <input type="number" id="ulumega_price" name="ulumega_price" step="0.01">
            </div>
        </div>

        <div class="form-group">
            <input type="checkbox" id="tour" name="tour" onclick="togglePriceInput('tour', 'tour_price_input')">
            <label for="tour">Tur</label>
            <div id="tour_price_input" style="display:none;">
                <label for="tour_price">Tur Fiyatı</label>
                <input type="number" id="tour_price" name="tour_price" step="0.01">
            </div>
        </div>

        <div class="form-group">
            <input type="checkbox" id="e_bike" name="e_bike" onclick="togglePriceInput('e_bike', 'ebike_price_input')">
            <label for="e_bike">E-Bike</label>
            <div id="ebike_price_input" style="display:none;">
                <label for="ebike_price">E-Bike Fiyatı</label>
                <input type="number" id="ebike_price" name="ebike_price" step="0.01">
            </div>
        </div>

        <div class="form-group">
            <label for="bib_price">Bib Fiyatı</label>
            <input type="number" id="bib_price" name="bib_price" step="0.01">
        </div>

        <div class="form-group">
            <label for="special_bib_price">Özel Bib Fiyatı</label>
            <input type="number" id="special_bib_price" name="special_bib_price" step="0.01">
        </div>

        <!-- PDF dosyası yükleme -->
        <div class="form-group">
            <label for="race_details_pdf">Yarış Detayları PDF</label>
            <input type="file" id="race_details_pdf" name="race_details_pdf" accept="application/pdf" required>
        </div>

        <button type="submit">Organizasyonu Ekle</button>
    </form>
</div>
</body>
</html>
