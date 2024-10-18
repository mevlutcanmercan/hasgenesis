<?php
include '../db/database.php';
include 'sidebar.php';
// Form gönderildiğinde
// IBAN ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $bank_name = isset($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
    $owner_first_name = isset($_POST['owner_first_name']) ? trim($_POST['owner_first_name']) : '';
    $owner_last_name = isset($_POST['owner_last_name']) ? trim($_POST['owner_last_name']) : '';
    $iban_number = isset($_POST['iban_number']) ? trim($_POST['iban_number']) : '';

    // Hata kontrolü
    $error_message = '';
    if (empty($bank_name) || empty($owner_first_name) || empty($owner_last_name) || empty($iban_number)) {
        $error_message = 'Tüm alanlar doldurulmalıdır.';
    } elseif (strlen($iban_number) < 15 || strlen($iban_number) > 34) {
        $error_message = 'IBAN numarası 15 ile 34 karakter arasında olmalıdır.';
    } else {
        // IBAN bilgilerini veritabanına ekle
        $query = "INSERT INTO iban (bank_name, owner_first_name, owner_last_name, iban_number) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $bank_name, $owner_first_name, $owner_last_name, $iban_number);

        if ($stmt->execute()) {
            $success_message = 'IBAN başarıyla eklendi!';
        } else {
            $error_message = 'Bir hata oluştu. Lütfen tekrar deneyin.';
        }
        $stmt->close();
    }
}

// IBAN silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $iban_id = isset($_POST['iban_id']) ? intval($_POST['iban_id']) : 0;

    if ($iban_id > 0) {
        // IBAN bilgilerini veritabanından sil
        $query = "DELETE FROM iban WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $iban_id);

        if ($stmt->execute()) {
            $success_message = 'IBAN başarıyla silindi!';
        } else {
            $error_message = 'Bir hata oluştu. Lütfen tekrar deneyin.';
        }
        $stmt->close();
    } else {
        $error_message = 'Geçersiz IBAN ID.';
    }
}

// IBAN verilerini almak için sorgu
$query = "SELECT * FROM iban";
$result = $conn->query($query);

// IBAN verilerini diziye alma
$ibanList = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ibanList[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBAN Ekle</title>
    <link rel="stylesheet" href="admincss/iban.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>IBAN Ekle</h2>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message); ?>
        </div>
    <?php elseif (isset($success_message)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <form action="add_iban.php" method="post">
        <div class="form-group">
            <label for="bank_name">Banka Adı:</label>
            <input type="text" class="form-control" id="bank_name" name="bank_name" required>
        </div>
        <div class="form-group">
            <label for="owner_first_name">IBAN Sahibi Adı:</label>
            <input type="text" class="form-control" id="owner_first_name" name="owner_first_name" required>
        </div>
        <div class="form-group">
            <label for="owner_last_name">IBAN Sahibi Soyadı:</label>
            <input type="text" class="form-control" id="owner_last_name" name="owner_last_name" required>
        </div>
        <div class="form-group">
            <label for="iban_number">IBAN Numarası:</label>
            <input type="text" class="form-control" id="iban_number" name="iban_number" required>
        </div>
        <input type="hidden" name="action" value="add">
        <button type="submit" class="btn btn-primary">Ekle</button>
    </form>

    <h3 class="mt-5">Kayıtlı IBANlar</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Banka Adı</th>
                <th>IBAN Sahibi Adı</th>
                <th>IBAN Sahibi Soyadı</th>
                <th>IBAN Numarası</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($ibanList)): ?>
                <?php foreach ($ibanList as $iban): ?>
                    <tr>
                        <td><?= htmlspecialchars($iban['bank_name']); ?></td>
                        <td><?= htmlspecialchars($iban['owner_first_name']); ?></td>
                        <td><?= htmlspecialchars($iban['owner_last_name']); ?></td>
                        <td><?= htmlspecialchars($iban['iban_number']); ?></td>
                        <td>
                            <form action="add_iban.php" method="post" style="display:inline;">
                                <input type="hidden" name="iban_id" value="<?= $iban['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger btn-sm">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">Hiç IBAN kaydı bulunmamaktadır.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>