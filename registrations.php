<?php
include 'dB/database.php'; // Database connection
include 'navbar.php';
include 'bootstrap.php';
include 'auth.php';

requireLogin(); // Check if the user is logged in
$user_id = $_SESSION['id_users'];

// Fetch user information
$stmt = $conn->prepare("SELECT name_users, surname_users, birthday_users, sex FROM users WHERE id_users= ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $second_name, $birthdate, $sex);
$stmt->fetch();
$stmt->close();

// Get organization ID from GET parameter
if (isset($_GET['organization_id'])) {
    $organization_id = intval($_GET['organization_id']);

    // Fetch organization information
    $organization_query = "SELECT * FROM organizations WHERE id = ?";
    $stmt = $conn->prepare($organization_query);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $organization_result = $stmt->get_result();
    $organization = $organization_result->fetch_assoc();
    
    if (!$organization) {
        die('Geçersiz organizasyon ID.');
    }

    // Get active races
    $active_races = [];
    if ($organization['downhill'] == 1) $active_races[] = 'downhill';
    if ($organization['enduro'] == 1) $active_races[] = 'enduro';
    if ($organization['tour'] == 1) $active_races[] = 'tour';
    if ($organization['ulumega'] == 1) $active_races[] = 'ulumega';

    // Fetch prices
    $prices_query = "SELECT * FROM prices WHERE organization_id = ?";
    $stmt = $conn->prepare($prices_query);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $prices_result = $stmt->get_result();

    $prices_row = $prices_result && $prices_result->num_rows > 0 ? $prices_result->fetch_assoc() : null; // Price data

} else {
    die('Organizasyon ID bulunamadı.');
}

// Calculate user's age
$birth_date = new DateTime($birthdate);
$today = new DateTime('today');
$age = $birth_date->diff($today)->y;

// Determine category based on age and sex
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
    $category = 'UNKNOWN'; // Fallback
}

// Initialize total price
$total_price = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bib = intval($_POST['bib_selection']);
    $selected_bicycle = intval($_POST['bicycle']); // Seçilen bisikletin ID'si
    $selected_races = $_POST['races'] ?? [];
    $extra_charge = 0;

    // Check if Bib number is already taken
    $bib_check_query = "SELECT Bib FROM registrations WHERE Bib = ? AND organization_id = ?";
    $stmt = $conn->prepare($bib_check_query);
    $stmt->bind_param("ii", $bib, $organization_id);
    $stmt->execute();
    $bib_check_result = $stmt->get_result();

    // Handle registration when "Genel Kayıt Ol" button is clicked
    if (isset($_POST['register'])) {
        if ($bib_check_result && $bib_check_result->num_rows > 0) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Bu Bib numarası zaten kayıtlı.',
                });
            </script>";
        } else {
            // Calculate price
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
                    case 'tour':
                        $total_price += $prices_row['tour_price'];
                        break;
                }
            }

            // Extra charge for custom Bib number
            if (!empty($bib)) {
                $extra_charge = 50; // Additional fee for custom Bib number
            }

            // Update total price
            $total_price += $extra_charge;

            // Yüklenen dosyaların kaydedileceği dizin
            $waiver_dir = 'documents/feragatname/';
            $receipt_dir = 'documents/receipt/';

            // Yüklenen dosyaların kaydedilmesi
            $feragatname = null;
            $price_document = null;

            if (isset($_FILES['waiver']) && $_FILES['waiver']['error'] === UPLOAD_ERR_OK) {
                $feragatname = $_FILES['waiver']['name'];
                move_uploaded_file($_FILES['waiver']['tmp_name'], $waiver_dir . $feragatname);
            }

            if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                $price_document = $_FILES['receipt']['name'];
                move_uploaded_file($_FILES['receipt']['tmp_name'], $receipt_dir . $price_document);
            }

            // Bisiklet bilgilerini kontrol etme
            $bike_query = "SELECT front_travel, rear_travel FROM bicycles WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($bike_query);
            $stmt->bind_param("ii", $selected_bicycle, $user_id);
            $stmt->execute();
            $bike_result = $stmt->get_result();
            $bike = $bike_result->fetch_assoc();

            if (!$bike) {
                die('Bisiklet bilgisi bulunamadı.');
            }

            $front_travel = $bike['front_travel'];
            $rear_travel = $bike['rear_travel'];

            // Süspansiyon mesafelerini organizasyon kuralları ile karşılaştır
            $travel_valid = true;
            $error_messages = [];

            // Ön süspansiyon mesafesini kontrol et
            if ($organization['min_front_suspension_travel'] !== null && $front_travel < $organization['min_front_suspension_travel']) {
                $travel_valid = false;
                $error_messages[] = "Seçtiğiniz bisikletin ön süspansiyon mesafesi bu organizasyon için yeterli değil.";
            }

            // Arka süspansiyon mesafesini kontrol et
            if ($organization['min_rear_suspension_travel'] !== null && $rear_travel < $organization['min_rear_suspension_travel']) {
                $travel_valid = false;
                $error_messages[] = "Seçtiğiniz bisikletin arka süspansiyon mesafesi bu organizasyon için yeterli değil.";
            }

            // Eğer mesafeler uygun değilse, hata mesajı göster
            if (!$travel_valid) {
                $error_message = implode(" ", $error_messages);
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: '$error_message',
                    });
                </script>";
                exit;
            }

            // Insert user registration into database
            $registration_query = "INSERT INTO registrations (Bib, first_name, second_name, organization_id, race_type, category, feragatname, price_document, created_time, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
            $stmt = $conn->prepare($registration_query);

            // Temporary variables for string data
            $status = 0; // Use 0 for 'pending'
            $race_type_json = json_encode($selected_races); // Race types encoded as JSON

            // Bind variables
            $stmt->bind_param("issssissi", $bib, $first_name, $second_name, $organization_id, $race_type_json, $category, $feragatname, $price_document, $status);

            // Sorguyu çalıştır
            if ($stmt->execute()) {
                // Kayıt başarılı
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Kayıt başarıyla eklendi.',
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
            }

            // Sorguyu kapat
            $stmt->close();
        }
    }
}

// Kullanıcıya ait bisikletleri çekmek
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

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8 ">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/registrations.css"> <!-- Your CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
    function updatePrice(prices) {
        let total = 0;
        const selectedRaces = document.querySelectorAll('input[name="races[]"]:checked');

        selectedRaces.forEach((checkbox) => {
            total += prices[checkbox.value] || 0; // Check price
        });

        // Check for Bib number input
        const bibInput = document.getElementById('bib_selection').value;
        if (bibInput) {
            total += 50; // Additional charge for custom Bib number
        }

        // Update total price
        document.getElementById('total_price').value = total + " TL"; // Update as input value
    }

    window.onload = function() {
        // Add prices to array
        const prices = {
            downhill: <?php echo isset($prices_row['downhill_price']) ? $prices_row['downhill_price'] : 0; ?>,
            enduro: <?php echo isset($prices_row['enduro_price']) ? $prices_row['enduro_price'] : 0; ?>,
            ulumega: <?php echo isset($prices_row['ulumega_price']) ? $prices_row['ulumega_price'] : 0; ?>,
            tour: <?php echo isset($prices_row['tour_price']) ? $prices_row['tour_price'] : 0; ?>
        };

        // Update price when checkboxes and Bib input change
        document.querySelectorAll('input[name="races[]"]').forEach((checkbox) => {
            checkbox.addEventListener('change', function() {
                updatePrice(prices);
            });
        });

        document.getElementById('bib_selection').addEventListener('input', function() {
            updatePrice(prices);
        });

        // Initial total price update
        updatePrice(prices);
    };
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
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="second_name" class="form-label">Soyisim</label>
                    <input type="text" class="form-control" id="second_name" name="second_name" value="<?php echo htmlspecialchars($second_name); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="bicycle" class="form-label">Bisiklet Seçiniz</label>
                    <select class="form-select" id="bicycle" name="bicycle" required>
                        <option value="">Bisiklet Seçiniz</option>
                        <?php
                        // Kullanıcıya ait bisikletleri döngüyle listeleme
                        if ($bike_result && $bike_result->num_rows > 0) {
                            while ($bike = $bike_result->fetch_assoc()) {
                                echo "<option value='{$bike['id']}'>{$bike['brandName']} - Ön: {$bike['front_travel']}mm, Arka: {$bike['rear_travel']}mm</option>";
                            }
                        } else {
                            echo "<option value=''>Bisiklet bulunamadı</option>";
                        }
                        ?>
                    </select>
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

                <h4>Yarış Seçenekleri</h4>
                <div>
                    <?php foreach ($active_races as $race): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="races[]" value="<?php echo htmlspecialchars($race); ?>" id="<?php echo htmlspecialchars($race); ?>" onchange="updatePrice(prices)">
                            <label class="form-check-label" for="<?php echo htmlspecialchars($race); ?>"><?php echo htmlspecialchars(ucfirst($race)); ?></label>
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

                <button type="submit" class="registerbtn" name="register">Kayıt Ol</button>

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

<script>
function toggleBibInput() {
    const bibInput = document.getElementById("bib_input");
    bibInput.style.display = document.getElementById("custom_bib").checked ? "block" : "none";
}
</script>
</body>
</html>
