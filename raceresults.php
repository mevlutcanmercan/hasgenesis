<?php
include 'db/database.php'; // Veritabanı bağlantısı
include 'bootstrap.php';

// Organizasyon ID'sini al
$organization_id = isset($_GET['organization_id']) ? (int)$_GET['organization_id'] : null;

// Kategori ve Yarış Türü Filtreleme
$category_filter = isset($_POST['category_filter']) ? $_POST['category_filter'] : '';
$race_type_filter = isset($_POST['race_type_filter']) ? $_POST['race_type_filter'] : 'Downhill'; // Varsayılan olarak 'Downhill' seçili

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
    <meta name="viewport" content="width=1100">
    <link rel="stylesheet" href="css/raceresults.css">
    <link rel="stylesheet" href="css/footer.css">
    <title><?php echo htmlspecialchars($organization_name); ?> Yarışı Sonuçları</title>
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center mb-4"><?php echo htmlspecialchars($organization_name); ?> Yarış Sonuçları</h1>

    <!-- Kategori ve Yarış Türü Filtreleme -->
    <form method="POST" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="category_filter" class="form-label">Kategori:</label>
                <select name="category_filter" id="category_filter" class="form-select">
                    <option value="">Tüm Kategoriler</option>
                    <option value="JUNIOR" <?php echo ($category_filter == 'JUNIOR') ? 'selected' : ''; ?>>Junior</option>
                    <option value="ELITLER" <?php echo ($category_filter == 'ELITLER') ? 'selected' : ''; ?>>Elitler</option>
                    <option value="MASTER B" <?php echo ($category_filter == 'MASTER B') ? 'selected' : ''; ?>>Master B</option>
                    <option value="MASTER A" <?php echo ($category_filter == 'MASTER A') ? 'selected' : ''; ?>>Master A</option>
                    <option value="KADINLAR" <?php echo ($category_filter == 'KADINLAR') ? 'selected' : ''; ?>>Kadınlar</option>
                    <!-- Diğer kategoriler eklenebilir -->
                </select>
            </div>
            <div class="col-md-4">
                <label for="race_type_filter" class="form-label">Yarış Türü:</label>
                <select name="race_type_filter" id="race_type_filter" class="form-select">
                    <option value="Downhill" <?php echo ($race_type_filter == 'Downhill') ? 'selected' : ''; ?>>Downhill</option>
                    <option value="Enduro" <?php echo ($race_type_filter == 'Enduro') ? 'selected' : ''; ?>>Enduro</option>
                    <option value="hardtail" <?php echo ($race_type_filter == 'hardtail') ? 'selected' : ''; ?>>Hardtail</option>
                    <option value="Ulumega" <?php echo ($race_type_filter == 'Ulumega') ? 'selected' : ''; ?>>Ulumega</option>
                    <option value="E-Bike" <?php echo ($race_type_filter == 'E-Bike') ? 'selected' : ''; ?>>E-Bike</option>
                    <!-- Diğer yarış türleri eklenebilir -->
                </select>
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
                <th>Fark</th>
                <th>Lap 1</th>
                <th>Lap 2</th>
                <th>Lap 3</th>
                <th>Lap 4</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php 
                    $counter = 1; // Sayaç her kategoriye göre sıralamaya başlar
                ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td> <!-- Sayaç her satırda 1 artacak -->
                        <td><?php echo htmlspecialchars($row['Bib']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['race_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo htmlspecialchars($row['time']); ?></td>
                        <td><?php echo htmlspecialchars($row['difference']); ?></td>
                        <td><?php echo htmlspecialchars($row['lap1']); ?></td>
                        <td><?php echo htmlspecialchars($row['lap2']); ?></td>
                        <td><?php echo htmlspecialchars($row['lap3']); ?></td>
                        <td><?php echo htmlspecialchars($row['lap4']); ?></td>
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
