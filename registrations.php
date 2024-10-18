<?php

// Veritabanı bağlantısı ve gerekli dosyaların dahil edilmesi
include 'dB/database.php'; 
include 'navbar.php';
include 'bootstrap.php';
include 'auth.php';

// Kullanıcının giriş yapmasını sağla
requireLogin(); 
$user_id = $_SESSION['id_users'];

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

// Bisikletin süspansiyon uygunluğunu kontrol eden fonksiyon
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
    $category2 = isset($_POST['category']) ? $_POST['category'] : null; // Sabit kategori değeri
    $category = isset($_POST['category']) ? $_POST['category'] : null;
    $bib = intval($_POST['bib_selection']);
    $selected_races = $_POST['races'] ?? [];
    $selected_bicycles = $_POST['bicycle_for'] ?? [];
    $special_bib = isset($_POST['custom_bib']);
    
    // Toplam fiyatı hesapla
    $total_price = calculateTotalPrice($selected_races, $prices_row, $bib, $special_bib);

        // Dosyaların yüklenmesi için hedef klasörler
        $base_url = '/hasgenesis'; // Ana URL
        $feragatname_dir = $base_url . '/documents/feragatname/';
        $receipt_dir = $base_url . '/documents/receipt/';

        // Feragatname dosyasını yükleme
        $feragatname = null;
        if (isset($_FILES['waiver']['name']) && $_FILES['waiver']['name'] != '') {
            $feragatname_filename = basename($_FILES['waiver']['name']);
            $feragatname_target = $_SERVER['DOCUMENT_ROOT'] . $feragatname_dir . $feragatname_filename;

            // Dosyayı taşı
            if (move_uploaded_file($_FILES['waiver']['tmp_name'], $feragatname_target)) {
                // Veritabanına kaydetmek için tam URL'yi oluştur
                $feragatname = $feragatname_dir . $feragatname_filename;
            } else {
                echo "<script>showErrorAlert('Feragatname dosyası yüklenirken bir hata oluştu.');</script>";
                exit();
            }
        }

        // Fiyat belgesi dosyasını yükleme
        $price_document = null;
        if (isset($_FILES['receipt']['name']) && $_FILES['receipt']['name'] != '') {
            $price_document_filename = basename($_FILES['receipt']['name']);
            $price_document_target = $_SERVER['DOCUMENT_ROOT'] . $receipt_dir . $price_document_filename;

            // Dosyayı taşı
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $price_document_target)) {
                // Veritabanına kaydetmek için tam URL'yi oluştur
                $price_document = $receipt_dir . $price_document_filename;
            } else {
                echo "<script>showErrorAlert('Fiyat belgesi dosyası yüklenirken bir hata oluştu.');</script>";
                exit();
            }
        }

    // Bib kontrolü
    if ($bib > 0 && checkBibExistence($conn, $bib, $organization_id)) {
        echo "<script>showErrorAlert('Bu Bib numarası zaten kayıtlı.');</script>";
        exit(); // Uyarı gösterildikten sonra işlem sonlandırılacak

    } else {
        // Bisiklet uygunluk kontrolü
        foreach ($selected_races as $race) {
            if (!isset($selected_bicycles[$race])) {
                echo "<script>showWarningAlert('Yarış türü için bir bisiklet seçmelisiniz.');</script>";
                exit();
            }
            $bicycle_id = intval($selected_bicycles[$race]);
            if (!checkBicycleSuspension($conn, $bicycle_id, $organization)) {
                echo "<script>
                alert('Seçtiğiniz bisiklet organizasyon gereksinimlerini karşılamıyor.');
                window.location.href = 'organizations.php'; // Yönlendirme yapılacak sayfa
              </script>";
        exit(); // PHP'nin çalışmasını burada durdurmak için
    }
    }
       // Kayıt işlemi
$stmt = $conn->prepare("INSERT INTO registrations (Bib, first_name, second_name, organization_id, race_type, category, feragatname, price_document, registration_price, created_time, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
$race_type_string = implode(',', $selected_races);  // Düz metin olarak
$feragatname = $_FILES['waiver']['name'] ?? null;
$price_document = $_FILES['receipt']['name'] ?? null;
$status = 0; // Beklemede

$stmt->bind_param("issssssssd", $bib, $user_details['name_users'], $user_details['surname_users'], $organization_id, $race_type_string, $category2, $feragatname, $price_document, $total_price, $status);

if ($stmt->execute()) {
    // En son eklenen kaydın ID'sini al
    $registration_id = $conn->insert_id;

    // Kategori bilgilerini yeni tabloya ekle
    $stmt_category = $conn->prepare("INSERT INTO registration_categories (registration_id, category) VALUES (?, ?)");
    $stmt_category->bind_param("is", $registration_id, $category);
    if (!$stmt_category->execute()) {
        echo "Kategori kaydı sırasında bir hata oluştu: " . $stmt_category->error;
    }

    // Seçilen bisikletleri kaydet
    foreach ($selected_races as $race) {
        $bicycle_id = intval($selected_bicycles[$race]);
        $stmt_bicycles = $conn->prepare("INSERT INTO registred_bicycles (registration_id, bicycles_id, race_type) VALUES (?, ?, ?)");
        $stmt_bicycles->bind_param("iis", $registration_id, $bicycle_id, $race);
        if (!$stmt_bicycles->execute()) {
            echo "Bisiklet kaydı sırasında bir hata oluştu: " . $stmt_bicycles->error;
        }
    }

    // Kullanıcı kayıtları tablosuna ekle
    $stmt_user_registration = $conn->prepare("INSERT INTO user_registrations (user_id, registration_id) VALUES (?, ?)");
    $stmt_user_registration->bind_param("ii", $user_id, $registration_id);
    if (!$stmt_user_registration->execute()) {
        echo "Kullanıcı kayıtları sırasında bir hata oluştu: " . $stmt_user_registration->error;
    }

    echo "<script>alert('Kayıt başarılı!'); window.location.href = 'account';</script>";

} else {
    echo "<script>alert('Kayıt sırasında bir hata oluştu.');</script>";
}
}
}
?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/footer.css">
        <link rel="stylesheet" href="css/registrations.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
    const prices = <?php echo json_encode($prices_row); ?>;

    function updatePrice(prices) {
        let total = 0;
        const selectedRaces = document.querySelectorAll('input[name="races[]"]:checked');

        selectedRaces.forEach((checkbox) => {
            total += parseFloat(prices[checkbox.value + '_price']) || 0;
        });

        const bibInput = document.getElementById("bib_selection").value;
        const customBibChecked = document.getElementById('custom_bib').checked;
        if (bibInput >= 0) {
            total += parseFloat(prices['bib_price']) || 0;
        }
        if (customBibChecked) {
            total += parseFloat(prices['special_bib_price']) || 0;
        }

        document.getElementById('total_price').value = total.toFixed(2) + " TL";
    }

    window.onload = function() {
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

        // Bib kontrol butonuna tıklama olayı
        document.getElementById("check_bib_button").addEventListener('click', function() {
            const bibInput = document.getElementById("bib_selection");
            const bibNumber = bibInput.value;
            const organizationId = <?php echo json_encode($organization_id); ?>;

            if (!bibNumber) {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Lütfen bir Bib numarası girin.',
                });
                return; // Eğer bib numarası yoksa, fonksiyondan çık
            }

            checkBibNumber(bibNumber, organizationId)
                .then(data => {
                    if (!data.isValid) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Geçersiz Bib Numarası',
                            text: 'Bu Bib numarası zaten kayıtlı.',
                        });
                        bibInput.value = ""; // Yanlış olduğunda inputu sıfırla
                        bibInput.dataset.isValid = "false"; // Geçersiz olarak işaretle
                    } else {
                        bibInput.dataset.isValid = "true"; // Geçerli olarak işaretle
                        Swal.fire({
                            icon: 'success',
                            title: 'Geçerli Bib Numarası',
                            text: 'Bib numaranız geçerli.',
                        });
                    }
                })
                .catch(error => {
                    console.error('Bib kontrolü sırasında hata oluştu:', error);
                });
        });
    };

    function showBikeSelection(checkbox) {
        const raceType = checkbox.value;
        const selectionDiv = document.getElementById('bike_selection_for_' + raceType);
        selectionDiv.style.display = checkbox.checked ? 'block' : 'none';
    }

    function toggleBibInput() {
        const bibInputDiv = document.getElementById("bib_input");
        const customBibChecked = document.getElementById("custom_bib").checked;
        bibInputDiv.style.display = customBibChecked ? "block" : "none";
        
        if (!customBibChecked) {
            document.getElementById("bib_selection").value = "";
        }
        
        updatePrice(prices);
    }

    function checkBibNumber(bibNumber, organizationId) {
        const formData = new FormData();
        formData.append('bib_number', bibNumber);
        formData.append('organization_id', organizationId);

        return fetch('check_bib_number.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json());
    }

    function validateBikeSelection(bikeSelect, organizationId) {
        const selectedOption = bikeSelect.options[bikeSelect.selectedIndex];
        const bicycleId = selectedOption.value;

        if (!bicycleId) {
            return;
        }

        const formData = new FormData();
        formData.append('bicycle_id', bicycleId);
        formData.append('organization_id', organizationId);

        fetch('suslevel.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.isValid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Geçersiz Bisiklet Seçimi',
                    text: 'Bu bisiklet organizasyon gereksinimlerini karşılamıyor.',
                });
                bikeSelect.selectedIndex = 0; // Seçimi sıfırla
            }
        })
        .catch(error => {
            console.error('Bisiklet kontrolü sırasında hata oluştu:', error);
        });
    }

    function validateForm() {
        const selectedRaces = document.querySelectorAll('input[name="races[]"]:checked');
        const bicycleSelections = document.querySelectorAll('select[name^="bicycle_for"]');
        const bibInput = document.getElementById("bib_selection");

        // Yarış türü seçilmediyse uyarı göster ve formu durdur
        if (selectedRaces.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'En az bir yarış türü seçmelisiniz.',
            });
            return false; // Formun gönderilmesini engelle
        }

        // Seçilen her yarış için bisiklet seçilmiş mi kontrol et
        for (let i = 0; i < selectedRaces.length; i++) {
            const raceValue = selectedRaces[i].value;
            const bikeSelection = document.querySelector(`select[name="bicycle_for[${raceValue}]"]`);

            if (bikeSelection && bikeSelection.value === "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Uyarı!',
                    text: `Yarış türü ${raceValue} için bir bisiklet seçmelisiniz.`,
                });
                return false; // Formun gönderilmesini engelle
            }
        }

        // Bib numarasının kontrolü
        if (bibInput.value && bibInput.dataset.isValid === "false") {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'Geçersiz Bib numarası, lütfen kontrol edin.',
            });
            return false; // Formun gönderilmesini engelle
        }

        return true; // Tüm kontroller geçtiyse form gönderilebilir
    }

    document.getElementById('raceForm').addEventListener('submit', function(event) {
        // Form gönderilmeden önce validateForm fonksiyonunu çalıştır
        if (!validateForm()) {
            event.preventDefault(); // Formu gönderme
        }
    });
</script>

    </head>
    <body>

    <div class="container">
        <div class="row">
            <!-- Sol sütun: Kullanıcı bilgileri ve kayıt formu -->
            <div class="col-md-8">
                <h3>Organizasyona Kayıt</h3>
                <div class="section-divider"></div> <!-- Bölüm Çizgisi -->

                <form id="raceForm" action="registrations.php?organization_id=<?= $organization_id ?>" method="post" enctype="multipart/form-data" onsubmit="return validateForm();">
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
    <input type="number" class="form-control" id="bib_selection" name="bib_selection" required>
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

                    <button type="submit"  id="check_bib_button" class="registerbtn btn btn-primary" name="register">Kayıt Ol</button>

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

            </div>
        </div>
    </div>
    <footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class='text-muted'>HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>   
</body>
</html>