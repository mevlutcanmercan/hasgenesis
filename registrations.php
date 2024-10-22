<?php
// Veritabanı bağlantısı ve gerekli dosyaların dahil edilmesi
include 'dB/database.php'; 
include 'navbar.php';
include 'bootstrap.php';
include 'auth.php';

// Kullanıcının giriş yapmasını sağla
requireLogin(); 
$user_id = $_SESSION['id_users'];

// IBAN verilerini almak için sorgu
$query = "SELECT bank_name, owner_first_name, owner_last_name, iban_number FROM iban";
$result = $conn->query($query);

// IBAN verilerini diziye alma
$ibanList = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ibanList[] = $row;
    }
}

// Bisiklet seçeneklerini veritabanından çek
function getBicycles($conn, $user_id) {
    $query = "SELECT b.id, br.brandName, b.front_travel, b.rear_travel 
              FROM bicycles b 
              JOIN brands br ON b.brand = br.id 
              WHERE b.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Organizasyon bilgilerini ve yarış tiplerini al
function getOrganizationDetails($conn, $organization_id) {
    $query = "SELECT * FROM organizations WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Organizasyonun fiyatlarını al
function getPrices($conn, $organization_id) {
    $query = "SELECT * FROM prices WHERE organization_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Kullanıcı bilgilerini çek
function getUserDetails($conn, $user_id) {
    $query = "SELECT name_users, surname_users, birthday_users, sex FROM users WHERE id_users = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Kullanıcının yaşını hesapla
function calculateAge($birthdate) {
    $birth_date = new DateTime($birthdate);
    $today = new DateTime('today');
    return $birth_date->diff($today)->y;
}

// Kategoriyi belirle
function determineCategory($age, $sex) {
    if ($age >= 14 && $age <= 21 && $sex != 'Kadın') return 'JUNIOR';
    if ($age >= 22 && $age <= 35 && $sex != 'Kadın') return 'ELITLER';
    if ($age >= 36 && $age <= 45 && $sex != 'Kadın') return 'MASTER A';
    if ($age >= 46 && $sex != 'Kadın') return 'MASTER B';
    if ($age >= 17 && $sex == 'Kadın') return 'KADINLAR';
    if ($age >= 17) return 'E-BIKE';
    return 'UNKNOWN';
}

// Fiyatı hesapla
function calculateTotalPrice($selected_races, $prices_row, $bib, $special_bib) {
    $total_price = 0;
    foreach ($selected_races as $race) {
        $total_price += $prices_row[$race . '_price'];
    }
    // Bib ve özel bib ücretleri ekle
    if ($bib >= 0) $total_price += $prices_row['bib_price'];
    if ($special_bib) $total_price += $prices_row['special_bib_price'] - $prices_row['bib_price'];
    return $total_price;
}

// Bib numarasının zaten var olup olmadığını kontrol et
function checkBibExistence($conn, $bib, $organization_id) {
    $query = "SELECT Bib FROM registrations WHERE Bib = ? AND organization_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $bib, $organization_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Seçilen bisikletin yarışa uygun olup olmadığını kontrol et
function checkBicycleSuspension($conn, $bicycle_id, $organization) {
    $front_travel = 0;
    $rear_travel = 0;
    $query = "SELECT front_travel, rear_travel FROM bicycles WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $bicycle_id);
    $stmt->execute();
    $stmt->bind_result($front_travel, $rear_travel);
    if ($stmt->fetch()) {
        return $front_travel >= $organization['min_front_suspension_travel'] && 
               $rear_travel >= $organization['min_rear_suspension_travel'];
    }
    return false;
}

// Kullanıcı bilgileri
$user_details = getUserDetails($conn, $user_id);
$age = calculateAge($user_details['birthday_users']);
$category = determineCategory($age, $user_details['sex']);

if (isset($_GET['organization_id'])) {
    $organization_id = intval($_GET['organization_id']);
    $organization = getOrganizationDetails($conn, $organization_id);
    $prices_row = getPrices($conn, $organization_id);
    
    // Aktif olan yarışları almak için sorgu
    $sql = "SELECT downhill, enduro, tour, ulumega, e_bike FROM organizations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Aktif olan yarışları diziye ekleyin
    $active_races = [];
    if ($row['downhill'] == 1) $active_races[] = 'downhill';
    if ($row['enduro'] == 1) $active_races[] = 'enduro';
    if ($row['tour'] == 1) $active_races[] = 'tour';
    if ($row['ulumega'] == 1) $active_races[] = 'ulumega';
    if ($row['e_bike'] == 1) $active_races[] = 'e_bike';
    
    // Bisiklet seçeneklerini al
    $bike_options = getBicycles($conn, $user_id);
} else {
    die('Organizasyon ID bulunamadı.');
}
// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = $_POST['category'] ?? null; // Sabit kategori değeri
    $bib = intval($_POST['bib_selection']);
    $selected_races = $_POST['races'] ?? [];
    $selected_bicycles = $_POST['bicycle_for'] ?? [];
    $special_bib = isset($_POST['custom_bib']);
    $error_message = 'Bu bib numarası zaten kayıtlı. Lütfen başka bir bib numarası girin.'; // Hata mesajı

    // Toplam fiyatı hesapla
    $total_price = calculateTotalPrice($selected_races, $prices_row, $bib, $special_bib);

    // Belgeleri kaydetmek için klasör yolları
    $feragatname_dir = 'documents/feragatname/';
    $receipt_dir = 'documents/receipt/';
    
    // Dizinlerin varlığını kontrol et ve oluştur
    if (!is_dir($feragatname_dir)) {
        mkdir($feragatname_dir, 0755, true); // Eğer dizin yoksa oluştur
    }
    
    if (!is_dir($receipt_dir)) {
        mkdir($receipt_dir, 0755, true); // Eğer dizin yoksa oluştur
    }
    
    // Maksimum dosya boyutu
    $max_file_size = 7 * 1024 * 1024; // 7 MB

    // Feragatname dosyasını yükleme
    if (isset($_FILES['waiver']) && $_FILES['waiver']['error'] === UPLOAD_ERR_OK) {
        // Dosya boyutunu kontrol et
        if ($_FILES['waiver']['size'] <= $max_file_size) {
            $feragatname_filename = basename($_FILES['waiver']['name']);
            $feragatname_target = $feragatname_dir . $feragatname_filename;
            if (move_uploaded_file($_FILES['waiver']['tmp_name'], $feragatname_target)) {
                $feragatname = $feragatname_filename; // Sadece dosya adını sakla
            }
        } else {
            // Yükleme işlemini engelle ve tüm işlemleri durdur
            exit('Feragatname belgesi 7 MB\'dan büyük olamaz. Lütfen uygun boyutta bir dosya yükleyin.');
        }
    }

    // Fiyat belgesi dosyasını yükleme
    $price_document = null;
    if (isset($_FILES['receipt']['name']) && $_FILES['receipt']['name'] != '') {
        $price_document_filename = basename($_FILES['receipt']['name']);
        $price_document_target = $receipt_dir . $price_document_filename;
        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $price_document_target)) {
            $price_document = $price_document_filename; // Sadece dosya adını sakla
        }
    }
    if ($bib > 0 && checkBibExistence($conn, $bib, $organization_id)) {
        ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: '<?= $error_message ?>',
            });
        </script>
        <?php 
    } else{

    // Bisiklet uygunluk kontrolü
    foreach ($selected_races as $race) {
        if (!isset($selected_bicycles[$race])) {
            exit(); // Yarış türü için bisiklet seçilmemişse işlemi sonlandır
        }
        $bicycle_id = intval($selected_bicycles[$race]);
        if (!checkBicycleSuspension($conn, $bicycle_id, $organization)) {
            exit(); // Bisiklet organizasyon gereksinimlerini karşılamıyorsa işlemi sonlandır
        }
    }

    // Kayıt işlemi
    $stmt = $conn->prepare("INSERT INTO registrations (Bib, first_name, second_name, organization_id, race_type, category, feragatname, price_document, registration_price, created_time, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    $race_type_string = implode(',', $selected_races); // Düz metin olarak
    $status = 0; // Beklemede

    $stmt->bind_param("issssssssd", $bib, $user_details['name_users'], $user_details['surname_users'], $organization_id, $race_type_string, $category, $feragatname, $price_document, $total_price, $status);

    if ($stmt->execute()) {
        // En son eklenen kaydın ID'sini al
        $registration_id = $conn->insert_id;

        // Kategori bilgilerini yeni tabloya ekle
        $stmt_category = $conn->prepare("INSERT INTO registration_categories (registration_id, category) VALUES (?, ?)");
        $stmt_category->bind_param("is", $registration_id, $category);
        $stmt_category->execute(); // Hata kontrolü yapılmadı

        // Seçilen bisikletleri kaydet
        foreach ($selected_races as $race) {
            $bicycle_id = intval($selected_bicycles[$race]);
            $stmt_bicycles = $conn->prepare("INSERT INTO registred_bicycles (registration_id, bicycles_id, race_type) VALUES (?, ?, ?)");
            $stmt_bicycles->bind_param("iis", $registration_id, $bicycle_id, $race);
            $stmt_bicycles->execute(); // Hata kontrolü yapılmadı
        }

        // Kullanıcı kayıtları tablosuna ekle
        $stmt_user_registration = $conn->prepare("INSERT INTO user_registrations (user_id, registration_id) VALUES (?, ?)");
        $stmt_user_registration->bind_param("ii", $user_id, $registration_id);
        $stmt_user_registration->execute(); // Hata kontrolü yapılmadı

        echo "<script>window.location.href = 'account';</script>"; // Başarılı işlem sonrasında yönlendirme
    } else {
        exit(); // Kayıt hatası durumunda işlemi sonlandır
    }
}}
?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=1000">
        <link rel="stylesheet" href="css/footer.css">
        <link rel="stylesheet" href="css/registrations.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="js/registeralerts.js"></script>


        <script>

        // PHP'den gelen fiyat bilgilerini JavaScript'e aktar
        const prices = <?php echo json_encode($prices_row); ?>;

        function updatePrice(prices) {
    let total = 0;
    const selectedRaces = document.querySelectorAll('input[name="races[]"]:checked');

    selectedRaces.forEach((checkbox) => {
        total += parseFloat(prices[checkbox.value + '_price']) || 0; // Yarış fiyatını kontrol et
    });

    // Bib fiyatını ekle
    const bibInput = document.getElementById("bib_selection").value;
    const customBibChecked = document.getElementById('custom_bib').checked;
    if (bibInput >= 0) { // Bib numarası girilmişse
        total += parseFloat(prices['bib_price']) || 0; // Normal bib fiyatını ekle
    }
    if (customBibChecked) {
        total += parseFloat(prices['special_bib_price']) || 0; // Özel bib fiyatını ekle
    }

    document.getElementById('total_price').value = total.toFixed(2) + " TL";
}




    window.onload = function() {
        // Checkbox ve Bib girişi değiştiğinde fiyatı güncelle
        document.querySelectorAll('input[name="races[]"]').forEach((checkbox) => {
            checkbox.addEventListener('change', function() {
                updatePrice(prices);
                showBikeSelection(this);
            });
        });

        document.getElementById('custom_bib').addEventListener('change', function() {
            toggleBibInput();
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
        const bibInputDiv = document.getElementById("bib_input");
        const bibInput = document.getElementById("bib_selection");
        const customBibChecked = document.getElementById("custom_bib").checked;
        bibInputDiv.style.display = customBibChecked ? "block" : "none";
        
        if (!customBibChecked) {
            // Inputu sıfırla
            bibInput.value = "";
            bibInput.removeAttribute("required");
        } else {
            bibInput.setAttribute("required", "required");
        }
        
        // Fiyatı güncelle
        updatePrice(prices);
    }


    
    function validateBib(bib, organizationId) {
    return new Promise((resolve, reject) => {
        // AJAX isteği
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'check_bib_number.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.exists) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'Bu bib zaten kayıtlı.',
                    });
                    resolve(false); // Bib mevcut, form gönderilmesin
                } else {
                    resolve(true); // Bib mevcut değil, form gönderilebilir
                }
            } else {
                reject(xhr.statusText);
            }
        };

        xhr.send(`bib=${bib}&organization_id=${organizationId}`);
    });
}

    function validateForm() {

        const selectedRaces = document.querySelectorAll('input[name="races[]"]:checked');
        if (selectedRaces.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'En az bir yarış türü seçmelisiniz.',
            });
            return false; // Formun gönderilmesini engelle
        }
        return true; // Form gönderilebilir

    }

    

    function validateBikeSelection(bikeSelect, organizationId) {
    const selectedOption = bikeSelect.options[bikeSelect.selectedIndex];
    const bicycleId = selectedOption.value;

    if (!bicycleId) {
        return; // Seçim yapılmamış, kontrol etme
    }

    // Bisiklet ID ve organizasyon ID'si ile AJAX isteği yap
    const formData = new FormData();
    formData.append('bicycle_id', bicycleId);
    formData.append('organization_id', organizationId);

    fetch('suslevel.php', { // Dosya yolunu kontrol edin
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.isValid) {
            Swal.fire({
                icon: 'warning',
                title: 'Geçersiz Bisiklet Seçimi',
                text: 'Seçtiğiniz bisiklet organizasyon gereksinimlerini karşılamıyor.'
            });

            // Yanlış seçim yapıldığında combobox'u sıfırla
            bikeSelect.value = "";
        }
    })
    .catch(error => {
        console.error('Bisiklet kontrolü sırasında hata oluştu:', error);
    });
}

        </script>
    </head>
    <body>

    <div class="container">
        <div class="row">
            <!-- Sol sütun: Kullanıcı bilgileri ve kayıt formu -->
            <div class="col-md-8">
                <h3>Organizasyona Kayıt</h3>
                <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

                <form action="" method="post" enctype="multipart/form-data" onsubmit="return validateForm();">
                        <div class="mb-3">
                <label for="first_name" class="form-label">İsim</label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_details['name_users']); ?>" required readonly>
            </div>
            <div class="mb-3">
                <label for="second_name" class="form-label">Soyisim</label>
                <input type="text" class="form-control" id="second_name" name="second_name" value="<?php echo htmlspecialchars($user_details['surname_users']); ?>" required readonly>
            </div>

                    <div class="mb-3">
                        <input type="checkbox" id="custom_bib" name="custom_bib" onchange="toggleBibInput()">
                        <label for="custom_bib" class="form-label">Özel Bib Numarası Almak İstiyorum</label>
                    </div>

                    <div id="bib_input" style="display:none;">
                        <label for="bib_selection" class="form-label">Bib Numarası</label>
                        <input type="number" class="form-control" id="bib_selection" name="bib_selection">
                    </div>

                 <div class="mb-3">
                    <label class="form-label">Kategori</label>
                <!-- Kategori bilgisi görüntülenen, ama sunucuya gönderilmeyen bir alan -->
                <input type="text" class="form-control" id="category_display" name="category_display" value="<?php echo htmlspecialchars($category); ?>" disabled>

                <!-- Kategori bilgisi sunucuya gönderilecek olan gizli bir alan -->
                <input type="hidden" id="category_hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">

                </div>

                    <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

                    <div class="mb-3">
                        <!-- Yarış türleri -->
                        <label class="form-label">Yarış Türleri:</label>
                        <?php foreach ($active_races as $race): ?>
                            <div>
                                <input type="checkbox" name="races[]" value="<?php echo $race; ?>" id="<?php echo $race; ?>" onclick="showBikeSelection(this)">
                                <label for="<?php echo $race; ?>"><?php echo ucfirst($race); ?></label>

                                <div id="bike_selection_for_<?php echo $race; ?>" style="display: none;">
                                    <label for="bicycle_for_<?php echo $race; ?>">Bisiklet Seç:</label>
                                    <select name="bicycle_for[<?php echo $race; ?>]" class="form-control" onchange="validateBikeSelection(this, <?php echo $organization_id; ?>)">
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

                    <button type="submit" class="btn-primary" name="register">Kayıt Ol</button>

                </form>
            </div>

                <!-- Sağ sütun: Organizasyon bilgileri -->
        <div class="col-md-4">
            <h3>Organizasyon Bilgileri</h3>
            <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

            <p><strong>Organizasyon Adı:</strong> <?php echo htmlspecialchars($organization['name']); ?></p>
            <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

            <h4>Toplam Fiyat</h4>
            <input type="text" id="total_price" class="form-control mb-3" value="0.00 TL" readonly>

            <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

            <h4>IBAN Bilgileri</h4>
            <div class="iban-list">
                <?php if (!empty($ibanList)): ?>
                    <ul class="list-group">
                        <?php foreach ($ibanList as $iban): ?>
                            <li class="list-group-item">
                                <strong>Banka:</strong> <?php echo htmlspecialchars($iban['bank_name']); ?><br>
                                <strong>Sahibi:</strong> <?php echo htmlspecialchars($iban['owner_first_name'] . ' ' . $iban['owner_last_name']); ?><br>
                                <strong>IBAN:</strong> <?php echo htmlspecialchars($iban['iban_number']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>IBAN bilgisi bulunmamaktadır.</p>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>
    <footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class='text-muted'>HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>   
<script>
    // Dosya boyutunu kontrol eden fonksiyon
    function checkFileSize(inputId, maxFileSize) {
        const fileInput = document.getElementById(inputId);
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.size > maxFileSize) {
                alert('Seçtiğiniz dosya 7 MB\'dan büyük olamaz. Lütfen uygun boyutta bir dosya yükleyin.');
                this.value = ''; // Dosya alanını temizle
                return false; // Yüklemeyi engelle
            }
        });
    }

    // Boyut sınırı
    const maxFileSize = 7 * 1024 * 1024; // 7 MB

    // Dosya boyutlarını kontrol et
    checkFileSize('waiver', maxFileSize);
    checkFileSize('receipt', maxFileSize);
</script>
</body>
</html>