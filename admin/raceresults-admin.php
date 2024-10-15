<?php
include 'sidebar.php';
include '../db/database.php'; // Veritabanı bağlantısı
include '../bootstrap.php';

// Kullanıcı giriş kontrolü
$user_id = isset($_SESSION['id_users']) ? $_SESSION['id_users'] : null;

// Organizasyon Seçimi
$organization_id = isset($_POST['organization_id']) ? (int)$_POST['organization_id'] : null;

// Yükleme İşlemi
if (isset($_FILES['file']) && $organization_id) {
    $file = $_FILES['file']['tmp_name'];
    
    if (($handle = fopen($file, 'r')) !== false) {
        // İlk satırı atla (başlıklar)
        fgetcsv($handle, 1000, ',');

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $place = (int)$data[0]; // Place
            $bib = (int)$data[1]; // Bib No
            $name = $data[2]; // Name
            $distance = $data[3]; // Distance (bunu race_type olarak alacağız)
            // Kategoriyi düzenle ve '- (-)' kısmını kaldır
            $category = preg_replace('/\d/', '', $data[4]); // Kategori (sadece yazılar)
            $category = preg_replace('/\s*-\s*\(.*?\)/', '', $category); // Kategori değerinden '- (-)' kısmını kaldır
            $category = trim($category); // Boşlukları temizle
             $time = $data[11]; // Time (son sütun)

            // Zaman formatını kontrol et ve düzenle
            $time = date('H:i:s', strtotime($time));

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
            } else {
                // Kullanıcı bulunamadığında null kalacak
            }

            // Elde edilen veriyi veritabanına ekle
            $insert_query = "INSERT INTO race_results (organization_id, user_id, place, Bib, name, race_type, category, time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiisssss", $organization_id, $user_id, $place, $bib, $name, $distance, $category, $time);
        $insert_stmt->execute();
        }
        fclose($handle);
        echo "<script>alert('Veriler başarıyla yüklendi.');</script>";
        } else {
        echo "<script>alert('Dosya yüklenirken bir hata oluştu.');</script>";
        }
        }

// Organizasyonları getir
$org_query = "SELECT id, name FROM organizations";
$org_result = $conn->query($org_query);
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
                <select name="organization_id" id="organization_id" class="form-select" required>
                    <option value="">Organizasyon Seçin</option>
                    <?php
                    while ($org = $org_result->fetch_assoc()) {
                        echo "<option value='{$org['id']}'" . ($organization_id == $org['id'] ? ' selected' : '') . ">{$org['name']}</option>";
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
                <th>Görüntüle</th>
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
                        <form action="raceresultsDetails-admin.php" method="GET">
                            <input type="hidden" name="organization_id" value="<?= $org['id']; ?>">
                            <button type="submit" class="btn btn-info">Görüntüle</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
