<?php
include 'db/database.php'; // Veritabanı bağlantısı
include 'bootstrap.php';

$organization_id = $_GET['organization_id'] ?? null; // Organizasyon ID'sini URL'den al
if (!$organization_id) {
    die("Organizasyon ID'si belirtilmedi.");
}

// Sadece onaylı kullanıcıları getirmek için sorguyu düzenleyin
$query = "SELECT * FROM registrations WHERE organization_id = ? AND approval_status = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $organization_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <title>Kayıtlı Kullanıcılar</title>
    <link rel="stylesheet" href="css/organization_registers.css"> <!-- CSS dosyasını bağla -->
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center mb-4">Kayıtlı Kullanıcılar</h1>
    
    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered custom-table">
            <thead>
                <tr>
                    <th>Bib No</th>
                    <th>İsim</th>
                    <th>Soyisim</th>
                    <th>Yarışacağı Yarışlar ve Kategorisi</th>
                    <th>Kayıt Zamanı</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?= $row['Bib'] == 0 ? "Henüz Atanmamıştır" : htmlspecialchars($row['Bib']); ?>
                        </td>
                        <td><?= htmlspecialchars($row['first_name']); ?></td>
                        <td><?= htmlspecialchars($row['second_name']); ?></td>
                        <td>
                            <?php
                            // Kategorileri sıralayarak listeleme
                            $categories = [];
                            if ($row['dh_kategori']) $categories[] = "Downhill: {$row['dh_kategori']}";
                            if ($row['end_kategori']) $categories[] = "Enduro: {$row['end_kategori']}";
                            if ($row['ulumega_kategori']) $categories[] = "Ulumega: {$row['ulumega_kategori']}";
                            if ($row['tour_kategori']) $categories[] = "Tour: {$row['tour_kategori']}";
                            if ($row['ebike_kategori']) $categories[] = "E-Bike: {$row['ebike_kategori']}";
                            echo implode("<br>", $categories);
                            ?>
                        </td>
                        <td><?= date("d-m-Y H:i", strtotime($row['created_time'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center text-muted">Bu organizasyona onaylı kullanıcı bulunmamaktadır.</p>
    <?php endif; ?>

</div>
</body>
</html>
