<?php
include 'dB/database.php';
include 'navbar.php';
include 'bootstrap.php';
include 'auth.php';

requireLogin();
$user_id = $_SESSION['id_users'];

// Fetch user information (e.g., first and second name)
$stmt = $conn->prepare("SELECT name_users, surname_users, birthday_users FROM users WHERE id_users= ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $second_name, $birthdate);
$stmt->fetch();
$stmt->close();

// Calculate user's age based on birthdate
$birth_date = new DateTime($birthdate);
$today = new DateTime('today');
$age = $birth_date->diff($today)->y;

// Assign category based on age
if ($age >= 14 && $age <= 21) {
    $category = 'JUNIOR';
} elseif ($age >= 22 && $age <= 35) {
    $category = 'ELITLER';
} elseif ($age >= 36 && $age <= 45) {
    $category = 'MASTER A';
} else {
    $category = 'KADINLAR/E-BIKE'; // Can be adjusted based on gender if needed
}

// Organizasyon ID'si GET parametresi ile geliyor
if (isset($_GET['organization_id'])) {
    $organization_id = intval($_GET['organization_id']);
    
    // Organizasyon bilgilerini al
    $org_query = "SELECT * FROM organizations WHERE id = $organization_id";
    $org_result = $conn->query($org_query);
    $organization = $org_result->fetch_assoc();
    
    if (!$organization) {
        die('Geçersiz organizasyon ID.');
    }
    
    // Organizasyona ait fiyatları al
    $prices_query = "SELECT * FROM prices WHERE organization_id = $organization_id";
    $prices_result = $conn->query($prices_query);

    if (!$prices_result) {
        die('Fiyatlar getirilemedi: ' . $conn->error);
    }
} else {
    die('Organizasyon ID bulunamadı.');
}

// Bib seçimi ve ek ücret işlemleri
$extra_charge = 0;
if (!empty($_POST['bib_selection'])) {
    $bib = intval($_POST['bib_selection']);
    $bib_check_query = "SELECT Bib FROM registrations WHERE Bib = $bib AND organization_id = $organization_id";
    $bib_check_result = $conn->query($bib_check_query);

    if ($bib_check_result->num_rows > 0) {
        echo "Bu Bib numarası zaten seçilmiş. Lütfen başka bir numara seçin.";
    } else {
        $extra_charge = 50; // Özel bib numarası için ek ücret
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/registrations.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <div class="row">
        <!-- Left Column: User Information and Registration Form -->
        <div class="col-md-8">
            <h3>Organizasyona Kayıt</h3>
            <form action="kaydet.php" method="post" enctype="multipart/form-data">
                <!-- User Information -->
                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="second_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="second_name" name="second_name" value="<?php echo htmlspecialchars($second_name); ?>" required>
                </div>

                <!-- Category (Auto-selected based on age) -->
                <div class="mb-3">
                    <label for="category" class="form-label">Kategori</label>
                    <input type="text" class="form-control" id="category" name="category" value="<?php echo $category; ?>" readonly>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary">Kaydol</button>
            </form>
        </div>

        <!-- Right Column: Prices and Bib Selection -->
        <div class="col-md-4">
            <h4>Fiyatlar</h4>
            <?php if ($prices_result->num_rows > 0) { ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Yarış</th>
                            <th>Fiyat (TL)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($price_row = $prices_result->fetch_assoc()) { ?>
                            <?php if ($price_row['downhill_price'] > 0) { ?>
                                <tr>
                                    <td>Downhill</td>
                                    <td><?php echo $price_row['downhill_price']; ?> TL</td>
                                </tr>
                            <?php } ?>
                            <?php if ($price_row['enduro_price'] > 0) { ?>
                                <tr>
                                    <td>Enduro</td>
                                    <td><?php echo $price_row['enduro_price']; ?> TL</td>
                                </tr>
                            <?php } ?>
                            <?php if ($price_row['ulumega_price'] > 0) { ?>
                                <tr>
                                    <td>Ulumega</td>
                                    <td><?php echo $price_row['ulumega_price']; ?> TL</td>
                                </tr>
                            <?php } ?>
                            <?php if ($price_row['tour_price'] > 0) { ?>
                                <tr>
                                    <td>Tour</td>
                                    <td><?php echo $price_row['tour_price']; ?> TL</td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p>Fiyat bilgisi bulunamadı.</p>
            <?php } ?>
            
            <!-- Bib Selection -->
            <h4>Bib Seçimi</h4>
            <div class="mb-3">
                <label for="bib_selection" class="form-label">Bib Numarası Seç</label>
                <input type="number" class="form-control" id="bib_selection" name="bib_selection" placeholder="Özel Bib Seç (Ekstra 50 TL)">
            </div>

            <!-- Proof Upload (Dekont Yükle) -->
            <h4>Dekont Yükle</h4>
            <div class="mb-3">
                <input type="file" class="form-control" id="proof_upload" name="proof_upload" accept="image/*,.pdf">
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('bib_selection').addEventListener('input', function() {
    const bibSelected = this.value;
    const basePrice = parseFloat(document.getElementById('base_price').innerText); // Varsayılan fiyatı al
    let totalPrice = basePrice;
    
    if (bibSelected) {
        totalPrice += 50; // Özel Bib seçildiyse ekstra ücret ekle
    }
    
    document.getElementById('total_price').innerText = totalPrice.toFixed(2) + ' TL'; // Toplam fiyatı güncelle
});

</script>

<!-- Include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
