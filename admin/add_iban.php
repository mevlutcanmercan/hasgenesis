<?php
include '../db/database.php';
include 'sidebar.php';

// Mesajları oturumdan al
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';

// Oturumdan mesajları temizle
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

// IBAN ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $bank_name = isset($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
    $owner_first_name = isset($_POST['owner_first_name']) ? trim($_POST['owner_first_name']) : '';
    $owner_last_name = isset($_POST['owner_last_name']) ? trim($_POST['owner_last_name']) : '';
    $iban_number = isset($_POST['iban_number']) ? trim($_POST['iban_number']) : '';

    // IBAN formatını düzelt (boşluk ekleyerek)
    $iban_number = strtoupper(str_replace(' ', '', $iban_number)); // Tüm boşlukları kaldır ve büyük harf yap
    $formatted_iban = $iban_number; // Formatlı IBAN için değişken

    // Boşluk ekleyerek formatla
    if (strlen($iban_number) > 0) {
        $formatted_iban = implode(' ', str_split($iban_number, 4)); // Her 4 karakterden sonra boşluk ekle
    }

    // Hata kontrolü
    if (empty($bank_name) || empty($owner_first_name) || empty($owner_last_name) || empty($iban_number)) {
        $_SESSION['error_message'] = 'Tüm alanlar doldurulmalıdır.';
    } elseif (strlen($iban_number) < 15 || strlen($iban_number) > 34) {
        $_SESSION['error_message'] = 'IBAN numarası 15 ile 34 karakter arasında olmalıdır.';
    } else {
        // IBAN'ı kontrol et
        $query = "SELECT * FROM iban WHERE iban_number = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $iban_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = 'Bu IBAN zaten kayıtlı!';
        } else {
            // IBAN bilgilerini veritabanına ekle
            $query = "INSERT INTO iban (bank_name, owner_first_name, owner_last_name, iban_number) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $bank_name, $owner_first_name, $owner_last_name, $formatted_iban);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'IBAN başarıyla eklendi!';
            } else {
                $_SESSION['error_message'] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
            }
            $stmt->close();
        }
    }

    // Sayfayı yenile
    header("Location: add_iban.php");
    exit();
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
            $_SESSION['success_message'] = 'IBAN başarıyla silindi!';
        } else {
            $_SESSION['error_message'] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = 'Geçersiz IBAN ID.';
    }

    // Sayfayı yenile
    header("Location: add_iban.php");
    exit();
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
    <meta name="viewport" content="width=1100">
    <title>IBAN Ekle</title>
    <link rel="stylesheet" href="admincss/iban.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script> <!-- SweetAlert Kütüphanesi -->
</head>
<body>
<div class="container mt-5">
    <h2>IBAN Ekle</h2>

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
            <input type="text" class="form-control" id="iban_number" name="iban_number" required maxlength="34" placeholder="TRxx xxxx xxxx xxxx xxxx xxxx xxxx xx" oninput="formatIBAN()">
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

<script>
    // Sayfa yüklendiğinde SweetAlert ile mesajları göster
    window.onload = function() {
        <?php if ($error_message): ?>
            swal("Hata!", "<?= htmlspecialchars($error_message); ?>", "error");
        <?php elseif ($success_message): ?>
            swal("Başarılı!", "<?= htmlspecialchars($success_message); ?>", "success");
        <?php endif; ?>
    };

    // IBAN numarasını formatla ve maksimum karakter sayısını kontrol et
    function formatIBAN() {
        const ibanInput = document.getElementById('iban_number');
        let ibanValue = ibanInput.value.replace(/\s+/g, ''); // Tüm boşlukları kaldır
        ibanValue = ibanValue.slice(0, 34); // Maksimum 34 karaktere kadar sınırla
        const formattedIBAN = ibanValue.replace(/(.{4})/g, '$1 ').trim(); // Her 4 karakterden sonra boşluk ekle
        ibanInput.value = formattedIBAN; // Formatlı IBAN'ı geri yükle
    }
</script>
</body>
</html>
