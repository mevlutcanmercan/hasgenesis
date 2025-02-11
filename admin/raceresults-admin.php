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

   // İlk satırdaki başlıkları al
$headerRow = $sheet->getRowIterator()->current();
$headerCells = $headerRow->getCellIterator();
$headerCells->setIterateOnlyExistingCells(false);

// Sütun indekslerini bulma
$differenceColumnIndex = null;
$lapColumnIndexes = [];
$columnIndex = 0;
foreach ($headerCells as $cell) {
    $headerValue = strtolower(trim($cell->getValue()));

    if ($headerValue === 'difference') {
        $differenceColumnIndex = $columnIndex;
    }

    if (strpos($headerValue, 'lap') !== false) {
        $lapColumnIndexes[] = $columnIndex; // "Lap" sütunlarını diziye ekle
    }

    $columnIndex++;
}

// Eğer "Difference" sütunu yoksa hata ver
if ($differenceColumnIndex === null) {
    die("Hata: 'Difference' sütunu bulunamadı.");
}

// Excel verisini okuma ve veritabanına aktarma
foreach ($sheet->getRowIterator() as $rowIndex => $row) {
    if ($rowIndex == 1) continue; // Başlık satırını atla

    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);

    $data = [];
    foreach ($cellIterator as $cell) {
        $data[] = $cell->getValue();
    }

    // Temel verileri al
    $place = (int)$data[0];
    $bib = (int)$data[1];
    $first_name = $data[3];
    $last_name = $data[4];
    $name = $first_name . ' ' . $last_name;
    $category = preg_replace('/\d/', '', $data[5]);
    $category = preg_replace('/\s*-\s*\(.*?\)/', '', $category);
    $category = preg_replace('/\s*\+\s*\(.*?\)/', '', $category);
    $category = trim($category);
    $time = $data[6];
    $difference = $data[$differenceColumnIndex];

    // Lap verilerini oku
    $laps = [];
    foreach ($lapColumnIndexes as $index) {
        $laps[] = $data[$index] ?? null;
    }

    // Eğer eksik lap sütunu varsa, boş string olarak tamamla
    while (count($laps) < 4) {
        $laps[] = null;
    }

    // Kullanıcı ID'sini adıyla eşleştir
    $user_query = "SELECT id_users FROM users WHERE LOWER(CONCAT(name_users, ' ', surname_users)) = LOWER(?)";
    $stmt = $conn->prepare($user_query);
    if (!$stmt) {
        die("Sorgu hazırlanamadı: " . $conn->error);
    }

    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    $user_id = null;
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id);
        $stmt->fetch();
    }

    // Veriyi veritabanına ekleme
    $insert_query = "INSERT INTO race_results (organization_id, user_id, place, Bib, name, race_type, category, time, difference, lap1, lap2, lap3, lap4)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);

    $insert_stmt->bind_param("iiissssssssss", $organization_id, $user_id, $place, $bib, $name, $race_type, $category, $time, $difference, $laps[0], $laps[1], $laps[2], $laps[3]);
    $insert_stmt->execute();
}

header("Location: raceresults-admin.php");
exit;
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
