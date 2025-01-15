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
        if (!move_uploaded_file($race_details_pdf['tmp_name'], $upload_file)) {
            echo "<script>alert('PDF dosyası yüklenirken bir hata oluştu.');</script>";
            exit; // Yükleme hatası varsa işlem sonlandırılır
        }
    } else {
        echo "<script>alert('Lütfen geçerli bir PDF dosyası yükleyin.');</script>";
        exit; // Geçersiz dosya hatası varsa işlem sonlandırılır
    }

    // Organizasyonu veritabanına ekle
    $stmt = $conn->prepare("INSERT INTO organizations (name, adress, details, register_start_date, last_register_day, type, downhill, enduro, hardtail, ulumega, e_bike, min_front_suspension_travel, min_rear_suspension_travel, race_details_pdf) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Evet/Hayır değerlerini ayarlama
    $downhill = isset($_POST['downhill']) ? 1 : 0;
    $enduro = isset($_POST['enduro']) ? 1 : 0;
    $hardtail = isset($_POST['hardtail']) ? 1 : 0;
    $ulumega = isset($_POST['ulumega']) ? 1 : 0;
    $e_bike = isset($_POST['e_bike']) ? 1 : 0;

    // Sorguyu çalıştır
    if ($stmt->execute([$organization_name, $address, $details, $register_start_date, $last_register_day, $organization_type, $downhill, $enduro, $hardtail, $ulumega, $e_bike, $min_front_suspension_travel, $min_rear_suspension_travel, $pdf_file_name])) {
        $organization_id = $conn->insert_id;

        // Fiyatları ekle
        $price_stmt = $conn->prepare("INSERT INTO prices (organization_id, downhill_price, enduro_price, ulumega_price, hardtail_price, e_bike_price, bib_price, special_bib_price) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        if ($price_stmt->execute([$organization_id, $downhill_price, $enduro_price, $ulumega_price, $hardtail_price, $ebike_price, $bib_price, $special_bib_price])) {
            // Yaş kategorilerini ekle
            $age_categories = [
                'downhill' => ['junior' => $_POST['downhill_age_junior'], 'elite' => $_POST['downhill_age_elite'], 'master_a' => $_POST['downhill_age_master_a'], 'master_b' => $_POST['downhill_age_master_b'], 'women' => $_POST['downhill_age_women']],
                'enduro' => ['junior' => $_POST['enduro_age_junior'], 'elite' => $_POST['enduro_age_elite'], 'master_a' => $_POST['enduro_age_master_a'], 'master_b' => $_POST['enduro_age_master_b'], 'women' => $_POST['enduro_age_women']],
                'ulumega' => ['junior' => $_POST['ulumega_age_junior'], 'elite' => $_POST['ulumega_age_elite'], 'master_a' => $_POST['ulumega_age_master_a'], 'master_b' => $_POST['ulumega_age_master_b'], 'women' => $_POST['ulumega_age_women']],
                'hardtail' => ['junior' => $_POST['hardtail_age_junior'], 'elite' => $_POST['hardtail_age_elite'], 'master_a' => $_POST['hardtail_age_master_a'], 'master_b' => $_POST['hardtail_age_master_b'], 'women' => $_POST['hardtail_age_women']],
                'e_bike' => ['junior' => $_POST['ebike_age_junior'], 'elite' => $_POST['ebike_age_elite'], 'master_a' => $_POST['ebike_age_master_a'], 'master_b' => $_POST['ebike_age_master_b'], 'women' => $_POST['ebike_age_women']]
            ];

            foreach ($age_categories as $category => $ages) {
                if (${$category}) { // Eğer kategori seçildiyse
                    $age_stmt = $conn->prepare("INSERT INTO age_category (organization_id, race_type, junior, elite, master_a, master_b, kadinlar) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $age_stmt->execute([$organization_id, $category, $ages['junior'], $ages['elite'], $ages['master_a'], $ages['master_b'], $ages['women']]);
                }
            }

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
    <style>
        .age-inputs {
            margin-top: 10px;
            margin-left: 3%;
            display: flex;
            gap: 10px; /* Alanlar arasında boşluk */
        }
        .age-inputs input {
            width: 70%; /* Alan genişliği */
            align-items: center !important;
        }
    </style>
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <script>
        function togglePriceAndAgeInput(checkboxId, priceInputId, ageInputId) {
            const checkbox = document.getElementById(checkboxId);
            const priceInput = document.getElementById(priceInputId);
            const ageInput = document.getElementById(ageInputId);

            if (checkbox.checked) {
                priceInput.style.display = 'block'; // Fiyat giriş alanını göster
                ageInput.style.display = 'flex'; // Yaş giriş alanını göster
            } else {
                priceInput.style.display = 'none'; // Fiyat giriş alanını gizle
                ageInput.style.display = 'none'; // Yaş giriş alanını gizle
                
            }
        }

        function validateAgeRanges() {
            const ageFields = [
                {name: 'downhill', inputs: ['downhill_age_junior', 'downhill_age_elite', 'downhill_age_master_a', 'downhill_age_master_b', 'downhill_age_women']},
                {name: 'enduro', inputs: ['enduro_age_junior', 'enduro_age_elite', 'enduro_age_master_a', 'enduro_age_master_b', 'enduro_age_women']},
                {name: 'ulumega', inputs: ['ulumega_age_junior', 'ulumega_age_elite', 'ulumega_age_master_a', 'ulumega_age_master_b', 'ulumega_age_women']},
                {name: 'hardtail', inputs: ['hardtail_age_junior', 'hardtail_age_elite', 'hardtail_age_master_a', 'hardtail_age_master_b', 'hardtail_age_women']},
                {name: 'e_bike', inputs: ['ebike_age_junior', 'ebike_age_elite', 'ebike_age_master_a', 'ebike_age_master_b', 'ebike_age_women']},
            ];

            for (const category of ageFields) {
                const ageValues = category.inputs.map(inputName => {
                    const inputValue = document.querySelector(`input[name="${inputName}"]`).value;
                    return inputValue.split('-').map(Number); // Yaş aralığını sayılara çevir
                });

                // Yaş aralıklarını kontrol et
                for (let i = 0; i < ageValues.length; i++) {
                    for (let j = i + 1; j < ageValues.length; j++) {
                        // Çakışma kontrolü
                        if ((ageValues[i][0] <= ageValues[j][1] && ageValues[j][0] <= ageValues[i][1]) || 
                            (ageValues[i][1] === Infinity && ageValues[j][0] <= ageValues[i][0]) || 
                            (ageValues[j][1] === Infinity && ageValues[i][0] <= ageValues[j][0])) {
                            return { valid: false, category: category.name };
                        }
                    }
                }
            }

            return { valid: true }; // Tüm kontroller başarılı
        }

        function handleSubmit(event) {
            const validationResult = validateAgeRanges();
            if (!validationResult.valid) {
                event.preventDefault(); // Formun gönderimini engelle
                // SweetAlert ile hata mesajı göster
                swal({
                    title: "Hata!",
                    text: `${validationResult.category} için yaş aralıkları çakışıyor!`,
                    icon: "error",
                    button: "Tamam",
                });
            }
        }

    </script>
</head>
<body>
<div class="form-container">
    <h2>Organizasyon Ekle</h2>
    <form action="" method="post" class="organization-form" enctype="multipart/form-data" onsubmit="handleSubmit(event)"> <!-- enctype ekledik -->
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
                <option value="Yarış">Yarış</option>
                <option value="Keşif">Keşif</option>
                <option value="Tur">Tur</option>
                <option value="Ücretsiz Etkinlik">Ücretsiz Etkinlik</option>
                <option value="Parkur Düzenleme">Parkur Düzenleme</option>
                <option value="Etkinlik">Etkinlik</option>
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
        <h3>Yarış Türleri Fiyatlarının ve Kategorilerinin Belirlenmesi (Ücretsiz Etkinliklerde de en az bir tür seçilmelidir !):</h3>
        

        <!-- Downhill -->
        <div class="form-group">
            <input type="checkbox" id="downhill" name="downhill" onclick="togglePriceAndAgeInput('downhill', 'downhill_price_input', 'downhill_age_input')">
            <label for="downhill">Downhill</label>
            <div id="downhill_price_input" style="display:none;">
                <label for="downhill_price">Downhill Fiyatı</label>
                <input type="number" id="downhill_price" name="downhill_price" step="0.01">
            </div>
            <div id="downhill_age_input" style="display:none;" class="age-inputs">
                <div>
                    <label>Junior</label>
                    <input type="text" name="downhill_age_junior" value="14-18" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Elite</label>
                    <input type="text" name="downhill_age_elite" value="19-29" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Master A</label>
                    <input type="text" name="downhill_age_master_a" value="30-39" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Master B</label>
                    <input type="text" name="downhill_age_master_b" value="40+" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Kadınlar</label>
                    <input type="text" name="downhill_age_women" value="17+" placeholder="Yaş Aralığı" readonly >
                </div>  
            </div>
        </div>

        <!-- Enduro -->
        <div class="form-group">
            <input type="checkbox" id="enduro" name="enduro" onclick="togglePriceAndAgeInput('enduro', 'enduro_price_input', 'enduro_age_input')">
            <label for="enduro">Enduro</label>
            <div id="enduro_price_input" style="display:none;">
                <label for="enduro_price">Enduro Fiyatı</label>
                <input type="number" id="enduro_price" name="enduro_price" step="0.01">
            </div>
            <div id="enduro_age_input" style="display:none;" class="age-inputs">
                <div>
                    <label>Junior</label>
                    <input type="text" name="enduro_age_junior" value="14-21" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Elite</label>
                    <input type="text" name="enduro_age_elite" value="22-35" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Master A</label>
                    <input type="text" name="enduro_age_master_a" value="36-45" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Master B</label>
                    <input type="text" name="enduro_age_master_b" value="46+" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Kadınlar</label>
                    <input type="text" name="enduro_age_women" value="17+" placeholder="Yaş Aralığı" readonly>
                </div>
            </div>
        </div>

        <!-- Ulumega -->
        <div class="form-group">
            <input type="checkbox" id="ulumega" name="ulumega" onclick="togglePriceAndAgeInput('ulumega', 'ulumega_price_input', 'ulumega_age_input')">
            <label for="ulumega">Ulumega</label>
            <div id="ulumega_price_input" style="display:none;">
                <label for="ulumega_price">Ulumega Fiyatı</label>
                <input type="number" id="ulumega_price" name="ulumega_price" step="0.01">
            </div>
            <div id="ulumega_age_input" style="display:none;" class="age-inputs">
                <div>
                    <label>Junior</label>
                    <input type="text" name="ulumega_age_junior" value="14-21" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Elite</label>
                    <input type="text" name="ulumega_age_elite" value="22-35" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Master A</label>
                    <input type="text" name="ulumega_age_master_a" value="36-45" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Master B</label>
                    <input type="text" name="ulumega_age_master_b" value="46+" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Kadınlar</label>
                    <input type="text" name="ulumega_age_women" value="17+" placeholder="Yaş Aralığı" readonly>
                </div>
            </div>
        </div>

        <!-- Tur -->
        <div class="form-group">
            <input type="checkbox" id="hardtail" name="hardtail" onclick="togglePriceAndAgeInput('hardtail', 'hardtail_price_input', 'hardtail_age_input')">
            <label for="hardtail">Hardtail</label>
            <div id="hardtail_price_input" style="display:none;">
                <label for="hardtail_price">Hardtail Fiyatı</label>
                <input type="number" id="hardtail_price" name="hardtail_price" step="0.01">
            </div>
            <div id="hardtail_age_input" style="display:none;" class="age-inputs">
                <div>
                    <label>Junior</label>
                    <input type="text" name="hardtail_age_junior" value="14-21" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Elite</label>
                    <input type="text" name="hardtail_age_elite" value="22-35" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Master A</label>
                    <input type="text" name="hardtail_age_master_a" value="36-45" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Master B</label>
                    <input type="text" name="hardtail_age_master_b" value="46+" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Kadınlar</label>
                    <input type="text" name="hardtail_age_women" value="17+" placeholder="Yaş Aralığı" readonly>
                </div>
            </div>
        </div>

        <!-- E-Bike -->
        <div class="form-group">
            <input type="checkbox" id="e_bike" name="e_bike" onclick="togglePriceAndAgeInput('e_bike', 'e_bike_price_input', 'ebike_age_input')">
            <label for="e_bike">E-Bike</label>
            <div id="e_bike_price_input" style="display:none;">
                <label for="e_bike_price">E-Bike Fiyatı</label>
                <input type="number" id="e_bike_price" name="e_bike_price" step="0.01">
            </div>
            <div id="ebike_age_input" style="display:none;" class="age-inputs">
                <div>
                    <label>Junior</label>
                    <input type="text" name="ebike_age_junior" value="100+" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Elite</label>
                    <input type="text" name="ebike_age_elite" value="17-35" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Master A</label>
                    <input type="text" name="ebike_age_master_a" value="36-60" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Master B</label>
                    <input type="text" name="ebike_age_master_b" value="100+" placeholder="Yaş Aralığı">
                </div>
                <div>
                    <label>Kadınlar</label>
                    <input type="text" name="ebike_age_women" value="17+" placeholder="Yaş Aralığı" readonly>
                </div>
            </div>
        </div>

        <!-- Bib Fiyatı -->
        <div class="form-group">
            <label for="bib_price">Yarış Numarası (Bib) Fiyatı</label>
            <input type="number" id="bib_price" name="bib_price" step="0.01">
        </div>

        <!-- Özel Yarış Numarası Fiyatı -->
        <div class="form-group">
            <label for="special_bib_price">Özel Yarış Numarası Fiyatı</label>
            <input type="number" id="special_bib_price" name="special_bib_price" step="0.01">
        </div>

        <!-- PDF Yükleme -->
        <div class="form-group">
            <label for="race_details_pdf">Yarış Detayları PDF</label>
            <input type="file" id="race_details_pdf" name="race_details_pdf" accept=".pdf" required>
        </div>

        <button type="submit">Organizasyonu Ekle</button>
    </form>
</div>
</body>
</html>
