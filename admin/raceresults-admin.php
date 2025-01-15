<?php
ob_start(); // Çıktıyı tamponla

include 'sidebar.php';
include '../db/database.php'; // Veritabanı bağlantısı
include '../bootstrap.php';
require '../vendor/autoload.php'; // PhpSpreadsheet için gerekli

use PhpOffice\PhpSpreadsheet\IOFactory; // PhpSpreadsheet IOFactory sınıfını kullan

// Kullanıcı giriş kontrolü
$user_id = isset($_SESSION['id_users']) ? $_SESSION['id_users'] : null;

// Organizasyon Seçimi
$organization_id = isset($_POST['organization_id']) ? (int)$_POST['organization_id'] : null;

// Yarış Tipi Seçimi
$race_type = isset($_POST['race_type']) ? $_POST['race_type'] : null;

// Yükleme İşlemi
if (isset($_FILES['file']) && $organization_id && $race_type) {
    $file = $_FILES['file']['tmp_name'];

    // PhpSpreadsheet ile XLS dosyasını aç
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();

    // Veriyi okuma (satır satır)
    foreach ($sheet->getRowIterator() as $rowIndex => $row) {
        // İlk satırı atla (başlıklar)
        if ($rowIndex == 1) {
            continue;
        }

        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false); // Tüm hücreleri oku, boş hücreler de dahil

        $data = [];
        foreach ($cellIterator as $cell) {
            $data[] = $cell->getValue(); // Hücre değerini al
        }

        // Verileri işleme
        $place = (int)$data[0]; // Place
        $bib = (int)$data[1]; // Bib No
        $first_name = $data[3]; // First Name
        $last_name = $data[4]; // Last Name
        $name = $first_name . ' ' . $last_name; // Tam isim (First Name + Last Name)
        $category = preg_replace('/\d/', '', $data[5]); // Kategori (sadece yazılar)
        $category = preg_replace('/\s*-\s*\(.*?\)/', '', $category); // Kategori değerinden '- (-)' kısmını kaldır
        $category = preg_replace('/\s*\+\s*\(.*?\)/', '', $category); // '+ (1984 VE ALTI)' kısmını temizle
        $category = trim($category); // Boşlukları temizle
        $time = $data[6]; // Time
        $difference = $data[7]; // Difference

        // Zaman formatını kontrol et ve düzenle
        if (strtolower($time) !== 'dns') {
            $time = date('H:i:s', strtotime($time));
        } else {
            $time = null; // Eğer DNS ise null atayın
        }

        // Kullanıcı ID'sini adıyla eşleştir
        $user_query = "SELECT u.id_users 
                       FROM users u 
                       WHERE LOWER(CONCAT(u.name_users, ' ', u.surname_users)) = LOWER(?)";
        $stmt = $conn->prepare($user_query);
        if (!$stmt) {
            die("Sorgu hazırlanamadı: " . $conn->error);
        }

        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->store_result();

        $user_id = null; // Varsayılan değer olarak null ata

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
        }

        // Elde edilen veriyi veritabanına ekle
        $insert_query = "INSERT INTO race_results (organization_id, user_id, place, Bib, name, race_type, category, time, difference)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiissssss", $organization_id, $user_id, $place, $bib, $name, $race_type, $category, $time, $difference);
        $insert_stmt->execute();
    }

    // Başarılı yükleme sonrası yönlendirme
    // header("Location: ../raceresults.php?organization_id=$organization_id&race_type=$race_type");
    header("Location: raceresults-admin.php");
    exit; // Yönlendirmeden sonra işlemi sonlandır
}

// Organizasyonları getir
$org_query = "SELECT id, name, downhill, enduro, hardtail, ulumega, e_bike FROM organizations";
$org_result = $conn->query($org_query);

// Yarış türlerini getir
$race_types = [];
if ($organization_id) {
    $organization_query = "SELECT downhill, enduro, hardtail, ulumega, e_bike FROM organizations WHERE id = ?";
    $stmt = $conn->prepare($organization_query);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $stmt->bind_result($downhill, $enduro, $hardtail, $ulumega, $e_bike);
    $stmt->fetch();

    if ($downhill) $race_types[] = 'downhill';
    if ($enduro) $race_types[] = 'enduro';
    if ($hardtail) $race_types[] = 'hardtail';
    if ($ulumega) $race_types[] = 'ulumega';
    if ($e_bike) $race_types[] = 'E-bike'; // 'E-bike' için büyük 'E' kullan
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <link rel="stylesheet" href="admincss/raceresults-admin.css">
    <title>Yarış Sonuçları Yönetimi</title>
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center mb-4">Yarış Sonuçları Yönetimi</h1>

    <!-- Organizasyon Seçimi ve Dosya Yükleme -->
    <form method="POST" enctype="multipart/form-data" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="organization_id" class="form-label">Organizasyon Seçin:</label>
                <select name="organization_id" id="organization_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">Organizasyon Seçin</option>
                    <?php
                    while ($org = $org_result->fetch_assoc()) {
                        echo "<option value='{$org['id']}'" . ($organization_id == $org['id'] ? ' selected' : '') . ">{$org['name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="race_type" class="form-label">Yarış Tipi Seç:</label>
                <select name="race_type" id="race_type" class="form-control" required>
                    <option value="">-- Yarış Tipi Seçiniz --</option>
                    <?php
                    foreach ($race_types as $type) {
                        echo "<option value='{$type}'" . ($race_type === $type ? ' selected' : '') . ">{$type}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="file" class="form-label">Sonuç Dosyasını Yükle:</label>
                <input type="file" name="file" id="file" class="form-control" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary mt-3">Sonuçları Yükle</button>
            </div>
        </div>
    </form>

    <!-- Organizasyonlar Tablosu -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Organizasyon</th>
                <th>Yarış Türleri</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Organizasyonları tekrar sorgula, kullanıcıdan gelen organizasyonları göstermek için
            $org_result->data_seek(0); // İkinci kez sorgulamak için verileri sıfırla
            while ($org = $org_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($org['name']); ?></td>
                    <td>
                        <form action="raceresultsDetails-admin" method="GET">
                            <input type="hidden" name="organization_id" value="<?= $org['id']; ?>">
                            <button type="submit" name="race_type" value="downhill" class="btn btn-info" <?= !$org['downhill'] ? '' : ''; ?>>Downhill</button>
                            <button type="submit" name="race_type" value="enduro" class="btn btn-info" <?= !$org['enduro'] ? '' : ''; ?>>Enduro</button>
                            <button type="submit" name="race_type" value="hardtail" class="btn btn-info" <?= !$org['hardtail'] ? '' : ''; ?>>hardtail</button>
                            <button type="submit" name="race_type" value="ulumega" class="btn btn-info" <?= !$org['ulumega'] ? '' : ''; ?>>Ulumega</button>
                            <button type="submit" name="race_type" value="E-bike" class="btn btn-info" <?= !$org['e_bike'] ? '' : ''; ?>>E-Bike</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
ob_end_flush(); // Tamponu boşalt ve çıktıyı gönder
