<?php
include 'dB/database.php'; 
include 'navbar.php';
include 'bootstrap.php';
include 'auth.php';

requireLogin(); 
$user_id = $_SESSION['id_users'];

$bike_query = "
    SELECT b.id, br.brandName, b.front_travel, b.rear_travel 
    FROM bicycles b
    JOIN brands br ON b.brand = br.id
    WHERE b.user_id = ?
";
$stmt = $conn->prepare($bike_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bike_result = $stmt->get_result();

// Bisiklet verilerini diziye al
$bike_options = [];
while ($bike = $bike_result->fetch_assoc()) {
    $bike_options[] = $bike;
}

// Kullanıcı bilgilerini al
$stmt = $conn->prepare("SELECT name_users, surname_users, birthday_users, sex FROM users WHERE id_users = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $second_name, $birthdate, $sex);
$stmt->fetch();
$stmt->close();

// GET parametresinden organizasyon ID'sini al
if (isset($_GET['organization_id'])) {
    $organization_id = intval($_GET['organization_id']);

    // Organizasyon bilgilerini al
    $organization_query = "SELECT * FROM organizations WHERE id = ?";
    $stmt = $conn->prepare($organization_query);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $organization_result = $stmt->get_result();
    $organization = $organization_result->fetch_assoc();
    
    if (!$organization) {
        die('Geçersiz organizasyon ID.');
    }

    // Aktif yarışları al
    $active_races = [];
    if ($organization['downhill'] == 1) $active_races[] = 'downhill';
    if ($organization['enduro'] == 1) $active_races[] = 'enduro';
    if ($organization['tour'] == 1) $active_races[] = 'tour';
    if ($organization['ulumega'] == 1) $active_races[] = 'ulumega';
    if ($organization['e_bike'] == 1) $active_races[] = 'e_bike';

    // Fiyatları al
    $prices_query = "SELECT * FROM prices WHERE organization_id = ?";
    $stmt = $conn->prepare($prices_query);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $prices_result = $stmt->get_result();

    $prices_row = $prices_result && $prices_result->num_rows > 0 ? $prices_result->fetch_assoc() : null; // Fiyat verileri

// Bib numarasına göre ekstra ücret hesaplama
$extra_charge = isset($_POST['bib_selection']) && !empty($_POST['bib_selection']) 
    ? $prices_row['special_bib_price'] 
    : $prices_row['bib_price'];


} else {
    die('Organizasyon ID bulunamadı.');
}

// Kullanıcının yaşını hesapla
$birth_date = new DateTime($birthdate);
$today = new DateTime('today');
$age = $birth_date->diff($today)->y;

// Yaşa ve cinsiyete göre kategoriyi belirle
if ($age >= 14 && $age <= 21 && $sex != 'Kadın') {
    $category = 'JUNIOR';
} elseif ($age >= 22 && $age <= 35 && $sex != 'Kadın') {
    $category = 'ELITLER';
} elseif ($age >= 36 && $age <= 45 && $sex != 'Kadın') {
    $category = 'MASTER A';
} elseif ($age >= 17 && $sex == 'Kadın') {
    $category = 'KADINLAR'; 
} elseif ($age >= 17) {
    $category = 'E-BIKE'; 
} else {
    $category = 'UNKNOWN'; // Yedek
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bib = intval($_POST['bib_selection']);
    $selected_races = $_POST['races'] ?? [];
    $selected_bicycles = $_POST['bicycle_for'] ?? []; // Her yarış türü için seçilen bisikletler
    $extra_charge = !empty($bib) ? 50 : 0; // Özel Bib numarası için ekstra ücret
    $total_price = 0; // Toplam fiyatı başlat

    // Bib numarasının kontrolü
    $bib_check_query = "SELECT Bib FROM registrations WHERE Bib = ? AND organization_id = ?";
    $stmt = $conn->prepare($bib_check_query);
    $stmt->bind_param("ii", $bib, $organization_id);
    $stmt->execute();
    $bib_check_result = $stmt->get_result();

    // Kayıt işlemleri için "Genel Kayıt Ol" butonuna tıklanma kontrolü
    if (isset($_POST['register'])) {
        if ($bib_check_result->num_rows > 0) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Bu Bib numarası zaten kayıtlı.',
                });
            </script>";
        } else {
            // Fiyat hesaplama
            foreach ($selected_races as $race) {
                switch ($race) {
                    case 'downhill':
                        $total_price += $prices_row['downhill_price'];
                        break;
                    case 'enduro':
                        $total_price += $prices_row['enduro_price'];
                        break;
                    case 'ulumega':
                        $total_price += $prices_row['ulumega_price'];
                        break;
                    case 'e_bike':
                        $total_price += $prices_row['ebike_price'];
                        break;
                    case 'tour':
                        $total_price += $prices_row['tour_price'];
                        break;
                }
            }

            // Toplam fiyat güncelle
            $total_price += $extra_charge;

            // Yüklenen dosyaların kaydedileceği dizin
            $waiver_dir = 'documents/feragatname/';
            $receipt_dir = 'documents/receipt/';

            $feragatname = isset($feragatname) ? $feragatname : null;
            $price_document = isset($price_document) ? $price_document : null;
            

            if (isset($_FILES['waiver']) && $_FILES['waiver']['error'] === UPLOAD_ERR_OK) {
                $feragatname = $_FILES['waiver']['name'];
                move_uploaded_file($_FILES['waiver']['tmp_name'], $waiver_dir . basename($feragatname));
            }

            if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                $price_document = $_FILES['receipt']['name'];
                move_uploaded_file($_FILES['receipt']['tmp_name'], $receipt_dir . basename($price_document));
            }

            // Öncelikle bisikletlerin organizasyonun süspansiyon gereksinimlerini karşılayıp karşılamadığını kontrol et
            $suspension_error = false;
            $error_message = '';

            foreach ($selected_races as $race) {
                if (!isset($selected_bicycles[$race])) {
                    $suspension_error = true;
                    $error_message = "Yarış türü '$race' için bir bisiklet seçmelisiniz.";
                    break;
                }

                $bicycle_id = intval($selected_bicycles[$race]);

                // Bisiklet bilgilerini al
                $bike_info_query = "SELECT front_travel, rear_travel FROM bicycles WHERE id = ?";
                $stmt = $conn->prepare($bike_info_query);
                $stmt->bind_param("i", $bicycle_id);
                $stmt->execute();
                $stmt->bind_result($front_travel, $rear_travel);
                if ($stmt->fetch()) {
                    // Organizasyonun minimum süspansiyon gereksinimleri ile karşılaştır
                    if ($front_travel < $organization['min_front_suspension_travel'] || $rear_travel < $organization['min_rear_suspension_travel']) {
                        $suspension_error = true;
                        $error_message = "Yarış türü '$race' için seçtiğiniz bisikletin süspansiyon değeri organizasyonun gereksinimlerini karşılamıyor.";
                        break;
                    }
                } else {
                    $suspension_error = true;
                    $error_message = "Seçilen bisiklet bulunamadı.";
                    break;
                }
                $stmt->close();
            }

            if ($suspension_error) {
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: '$error_message',
                    });
                </script>";
            } else {
                // Insert user registration into database
                $registration_query = "INSERT INTO registrations (Bib, first_name, second_name, organization_id, race_type, category, feragatname, price_document, created_time, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
                $stmt = $conn->prepare($registration_query);

                // Temporary variables for string data
                $status = 0; // Use 0 for 'pending'
                $race_type_json = json_encode($selected_races); // Race types encoded as JSON
                $feragatname = $_FILES['waiver']['name'] ?? null; // Waiver file check
                $price_document = $_FILES['receipt']['name'] ?? null; // Price document check

                // Bind variables
                $stmt->bind_param("issssissi", $bib, $first_name, $second_name, $organization_id, $race_type_json, $category, $feragatname, $price_document, $status);

                // Sorguyu çalıştır
                if ($stmt->execute()) {
                    // Kayıt başarılı, şimdi registred_bicycles tablosuna ekleme yap
                    $registration_id = $stmt->insert_id;
                    $stmt->close();

                    $insert_bicycles_query = "INSERT INTO registred_bicycles (registration_id, bicycles_id, race_type) VALUES (?, ?, ?)";
                    $stmt_bikes = $conn->prepare($insert_bicycles_query);

                    foreach ($selected_races as $race) {
                        $bicycle_id = intval($selected_bicycles[$race]);
                        $stmt_bikes->bind_param("iis", $registration_id, $bicycle_id, $race);
                        if (!$stmt_bikes->execute()) {
                            // Eğer bir hata olursa, tüm işlemi geri al
                            echo "<script>
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Hata!',
                                    text: 'Kayıt eklenirken bir hata oluştu: " . $stmt_bikes->error . "',
                                });
                            </script>";
                            // Optional: Rollback işlemi eklenebilir
                            exit();
                        }
                    }
                    $stmt_bikes->close();

                    // Başarılı kayıt mesajı
                    echo "<script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı!',
                            text: 'Kayıt başarıyla eklendi.',
                        }).then(() => {
                            window.location.reload();
                        });
                    </script>";
                } else {
                    // Kayıt başarısız
                    echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata!',
                            text: 'Kayıt eklenirken bir hata oluştu: " . $stmt->error . "',
                        });
                    </script>";
                    exit();
                }

            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/registrations.css"> <!-- Your CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
   function updatePrice(prices) {
    let total = 0;
    const selectedRaces = document.querySelectorAll('input[name="races[]"]:checked');

    selectedRaces.forEach((checkbox) => {
        total += prices[checkbox.value] || 0; // Yarış fiyatını kontrol et
    });

    // Bib numarası girişi kontrolü
    const bibInput = document.getElementById('bib_selection').value;
    const bibCharge = bibInput ? prices['special_bib_price'] : prices['bib_price'];
    total += bibCharge;

    // Toplam fiyatı güncelle
    document.getElementById('total_price').value = total + " TL"; // Input değeri olarak güncelle
}

    window.onload = function() {
        // Fiyatları diziye ekle
        const prices = {
            downhill: <?php echo isset($prices_row['downhill_price']) ? $prices_row['downhill_price'] : 0; ?>,
            enduro: <?php echo isset($prices_row['enduro_price']) ? $prices_row['enduro_price'] : 0; ?>,
            ulumega: <?php echo isset($prices_row['ulumega_price']) ? $prices_row['ulumega_price'] : 0; ?>,
            ebike: <?php echo isset($prices_row['ebike_price']) ? $prices_row['ebike_price'] : 0; ?>,
            tour: <?php echo isset($prices_row['tour_price']) ? $prices_row['tour_price'] : 0; ?>
        };

        // Checkbox ve Bib girişi değiştiğinde fiyatı güncelle
        document.querySelectorAll('input[name="races[]"]').forEach((checkbox) => {
            checkbox.addEventListener('change', function() {
                updatePrice(prices);
                showBikeSelection(this);
            });
        });

        document.getElementById('bib_selection').addEventListener('input', function() {
            updatePrice(prices);
        });

        // İlk toplam fiyat güncellemesi
        updatePrice(prices);
    };

    function showBikeSelection(checkbox) {
        const raceType = checkbox.value;
        const selectionDiv = document.getElementById('bike_selection_for_' + raceType);
        selectionDiv.style.display = checkbox.checked ? 'block' : 'none';
    }

    function toggleBibInput() {
        const bibInput = document.getElementById("bib_input");
        bibInput.style.display = document.getElementById("custom_bib").checked ? "block" : "none";
    }
    </script>
</head>
<body>

<div class="container">
    <div class="row">
        <!-- Left column: User info and registration form -->
        <div class="col-md-8">
            <h3>Organizasyona Kayıt</h3>
            <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

            <form action="" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="first_name" class="form-label">İsim</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required disabled>
                </div>
                <div class="mb-3">
                    <label for="second_name" class="form-label">Soyisim</label>
                    <input type="text" class="form-control" id="second_name" name="second_name" value="<?php echo htmlspecialchars($second_name); ?>" required disabled>
                </div>
                <div class="mb-3">
                    <input type="checkbox" id="custom_bib" onchange="toggleBibInput()">
                    <label for="custom_bib" class="form-label">Özel Bib Numarası Almak İstiyorum</label>
                </div>

                <div id="bib_input" style="display:none;">
                    <label for="bib_selection" class="form-label">Bib Numarası</label>
                    <input type="number" class="form-control" id="bib_selection" name="bib_selection">
                </div>
                <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

                <div class="mb-3">
                    <!-- Yarış türleri -->
                    <label>Yarış Türleri:</label>
                    <?php foreach ($active_races as $race): ?>
                        <div>
                            <input type="checkbox" name="races[]" value="<?php echo $race; ?>" id="<?php echo $race; ?>" onclick="showBikeSelection(this)">
                            <label for="<?php echo $race; ?>"><?php echo ucfirst($race); ?></label>

                            <div id="bike_selection_for_<?php echo $race; ?>" style="display: none;">
                                <label for="bicycle_for_<?php echo $race; ?>">Bisiklet Seç:</label>
                                <select name="bicycle_for[<?php echo $race; ?>]" class="form-control" >
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($bike_options as $bike): ?>
                                        <option value="<?php echo $bike['id']; ?>">
                                            <?php echo $bike['brandName'] . ' - Ön Süspansiyon: ' . $bike['front_travel'] . ' mm, Arka Süspansiyon: ' . $bike['rear_travel'] . ' mm'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

                <h4>Belgeler Yükle</h4>
                <div class="mb-3">
                    <label for="waiver" class="form-label">Feragatname Belgesi Yükle</label>
                    <input type="file" class="form-control" name="waiver" id="waiver" accept=".pdf, .jpg, .png" required>
                </div>
                <div class="mb-3">
                    <label for="receipt" class="form-label">Dekont Yükle</label>
                    <input type="file" class="form-control" name="receipt" id="receipt" accept=".pdf, .jpg, .png" required>
                </div>
                <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

                <button type="submit" class="registerbtn btn btn-primary" name="register">Kayıt Ol</button>

            </form>
        </div>

        <!-- Right column: Organization and Pricing Info -->
        <div class="col-md-4">
            <h3>Organizasyon Bilgileri</h3>
            <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

            <p><strong>Organizasyon Adı:</strong> <?php echo htmlspecialchars($organization['name']); ?></p>
            <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

            <h4>Toplam Fiyat</h4>
            <input type="text" id="total_price" class="form-control" value="0 TL" readonly>
        </div>
    </div>
</div>
</body>
</html>
