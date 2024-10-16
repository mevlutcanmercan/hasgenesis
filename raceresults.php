<?php
include 'db/database.php'; // Veritabanı bağlantısı
include 'bootstrap.php';

// Organizasyon ID'sini al
$organization_id = isset($_GET['organization_id']) ? (int)$_GET['organization_id'] : null;

// Kategori ve Yarış Türü Filtreleme
$category_filter = isset($_POST['category_filter']) ? $_POST['category_filter'] : '';
$race_type_filter = isset($_POST['race_type_filter']) ? $_POST['race_type_filter'] : '';

// Organizasyon adını çek
$org_query = "SELECT name FROM organizations WHERE id = ?";
$org_stmt = $conn->prepare($org_query);
$org_stmt->bind_param("i", $organization_id);
$org_stmt->execute();
$org_stmt->bind_result($organization_name);
$org_stmt->fetch();
$org_stmt->close();

// Yarış Sonuçlarını Filtrele
$query = "SELECT * FROM race_results WHERE organization_id = ?";
$params = [$organization_id];
$types = "i";

if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($race_type_filter)) {
    $query .= " AND race_type = ?";
    $params[] = $race_type_filter;
    $types .= "s";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport">
    <link rel="stylesheet" href="css/raceresults.css">
    <link rel="stylesheet" href="css/footer.css">
    <title><?php echo htmlspecialchars($organization_name); ?> Yarış Detayları</title>
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center mb-4"><?php echo htmlspecialchars($organization_name); ?> Yarış Detayları</h1>

    <!-- Kategori ve Yarış Türü Filtreleme -->
    <form method="POST" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="category_filter" class="form-label">Kategori:</label>
                <input type="text" name="category_filter" id="category_filter" class="form-control" placeholder="Kategori (Örn: JUNIOR)" value="<?php echo htmlspecialchars($category_filter); ?>">
            </div>
            <div class="col-md-4">
                <label for="race_type_filter" class="form-label">Yarış Türü:</label>
                <input type="text" name="race_type_filter" id="race_type_filter" class="form-control" placeholder="Yarış Türü (Örn: Dh1, Downhill)" value="<?php echo htmlspecialchars($race_type_filter); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filtrele</button>
            </div>
        </div>
    </form>
    
    <!-- Yarış Sonuçları Tablosu -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Sıra</th>
                <th>Bib No</th>
                <th>Ad</th>
                <th>Yarış Türü</th>
                <th>Kategori</th>
                <th>Süre</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['place']); ?></td>
                        <td><?php echo htmlspecialchars($row['Bib']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['race_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo htmlspecialchars($row['time']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Bu kriterlere uygun sonuç bulunamadı.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class='text-muted'>HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

</body>
</html>

<?php
// Veritabanı bağlantısını kapat
$stmt->close();
$conn->close();
?>
