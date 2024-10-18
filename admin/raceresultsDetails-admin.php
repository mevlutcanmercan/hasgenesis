<?php
include 'sidebar.php';
include '../db/database.php'; // Veritabanı bağlantısı
include '../bootstrap.php';

// Organizasyon ID'sini al
$organization_id = isset($_GET['organization_id']) ? (int)$_GET['organization_id'] : null;

// Kategori Filtreleme
$category_filter = isset($_POST['category_filter']) ? $_POST['category_filter'] : '';
$race_type_filter = isset($_POST['race_type_filter']) ? $_POST['race_type_filter'] : '';

// Organizasyonun adını çek
$org_query = "SELECT name FROM organizations WHERE id = ?";
$org_stmt = $conn->prepare($org_query);
$org_stmt->bind_param("i", $organization_id);
$org_stmt->execute();
$org_stmt->bind_result($organization_name);
$org_stmt->fetch();
$org_stmt->close();

// Yarış Türü
$race_type = isset($_GET['race_type']) ? $_GET['race_type'] : '';

// Yarış Sonuçlarını Filtrele
$query = "SELECT * FROM race_results WHERE organization_id = ?";
$params = [$organization_id];
$types = "i";

if ($category_filter) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if ($race_type) {
    $query .= " AND race_type = ?";
    $params[] = $race_type;
    $types .= "s";
}

// Sorgu çalıştırma
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
    <link rel="stylesheet" href="admincss/raceresults-details.css">
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
                <select name="category_filter" id="category_filter" class="form-select">
                    <option value="">Tüm Kategoriler</option>
                    <option value="JUNIOR" <?php echo ($category_filter == 'JUNIOR') ? 'selected' : ''; ?>>Junior</option>
                    <option value="ELITLER" <?php echo ($category_filter == 'ELITLER') ? 'selected' : ''; ?>>Elitler</option>
                    <option value="MASTER B" <?php echo ($category_filter == 'MASTER B') ? 'selected' : ''; ?>>Master B</option>
                    <option value="MASTER A" <?php echo ($category_filter == 'MASTER A') ? 'selected' : ''; ?>>Master A</option>
                    <!-- Diğer kategoriler eklenebilir -->
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filtrele</button>
            </div>
        </div>
    </form>
    
    <!-- Yarış Sonuçları Tablosu -->
    <table>
        <thead>
            <tr>
                <th>Sıra</th>
                <th>Bib No</th>
                <th>Ad</th>
                <th>Yarış Türü</th>
                <th>Kategori</th>
                <th>Süre</th>
                <th>Fark</th>
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
</body>
</html>
