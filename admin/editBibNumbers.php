<?php
// Veritabanı bağlantısı ve gerekli dosyaların dahil edilmesi
include '../db/database.php';
include 'sidebar.php';

// organization_id parametresini güvenli bir şekilde alın
if (isset($_GET['organization_id'])) {
    $organization_id = $_GET['organization_id'];
} else {
    die('organization_id parametresi eksik!');
}

// Kullanıcı adını ve soyadını getirmek için sorgu
$sql = "
    SELECT id, Bib, first_name, second_name 
    FROM registrations 
    WHERE organization_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $organization_id);
$stmt->execute();
$result = $stmt->get_result();

// Başarı ya da hata mesajını oturumdan al
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Mesajları gösterdikten sonra oturumdan sil
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// POST isteği kontrolü
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // organization_id değerini POST parametresinden alın
    $organization_id = $_POST['organization_id']; // Bu POST parametresini aldığımızdan emin olun
    $registration_ids = $_POST['registration_ids'];
    $new_bib_numbers = $_POST['new_bib_numbers'];
    $update_count = 0; // Kaç tane Bib güncellendiğini takip etmek için

    for ($i = 0; $i < count($registration_ids); $i++) {
        $id = $registration_ids[$i];
        $newBib = $new_bib_numbers[$i];

        // Yeni Bib numarasını kontrol et, boşsa güncelleme yapma
        if (!empty($newBib)) {
            // Aynı organizasyonda yeni Bib numarasının kullanılıp kullanılmadığını kontrol et
            $checkSql = "SELECT COUNT(*) as count FROM registrations WHERE Bib = ? AND organization_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("ii", $newBib, $organization_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $row = $checkResult->fetch_assoc();

            if ($row['count'] == 0) {
                // Eğer Bib numarası mevcut değilse güncelleme yap
                $updateSql = "UPDATE registrations SET Bib = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ii", $newBib, $id);
                $updateStmt->execute();
                $update_count++;
            } else {
                // Mevcut Bib numarası varsa hata mesajı oluştur
                $_SESSION['error_message'] = "Bib numarası '$newBib' zaten kayıtlı!";
            }
        }
    }

    // Başarılı güncellemeler için mesaj oluştur
    if ($update_count > 0) {
        $_SESSION['success_message'] = "$update_count kayıt başarıyla güncellendi!";
    }

    // Güncelleme tamamlandığında yönlendirme yapmadan önce başka çıktı olmadığından emin olun
    header("Location: editBibNumbers?organization_id=" . $organization_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bib Düzenle</title>
    <link rel="stylesheet" href="styles.css">
</head>
<style>
    /* CSS stilleri buraya yerleştirildi */
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        color: #333;
        margin: 0;
        padding: 20px;
        width: 60%;
        position: relative;
        left: 20%;
    }

    h1 {
        text-align: center;
        color: white;
        background-color: black;
        padding: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;   
    }

    table, th, td {
        border: 1px solid #ddd;
        padding: 10px;
    }

    th {
        background-color: #f2f2f2;
        color: #333;
    }

    /* Tüm satırların arka plan rengini beyaz yapıyoruz */
    tr {
        background-color: #ffffff; /* Tüm satırların arka planı beyaz */
    }

    td input[type="text"] {
        width: 100%;
        padding: 8px;
        box-sizing: border-box;
    }

    button {
        background-color: #4CAF50;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        margin-top: 20px;
        margin-left: 46%;
    }

    button:hover {
        background-color: #45a049;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        width: 60%;
        margin-left: auto;
        margin-right: auto;
    }

    .alert.success {
        background-color: #dff0d8;
        color: #3c763d;
        border: 1px solid #d6e9c6;
    }

    .alert.error {
        background-color: #f2dede;
        color: #a94442;
        border: 1px solid #ebccd1;
    }
</style>
<body>
<?php if ($success_message): ?>
    <div class="alert success"><?php echo $success_message; ?></div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert error"><?php echo $error_message; ?></div>
<?php endif; ?>

<h1>Bib Numaralarını Düzenle</h1>
<form action="editBibNumbers.php?organization_id=<?php echo $organization_id; ?>" method="post">
    <table>
        <tr>
            <th>Kayıt ID</th>
            <th>Ad</th>
            <th>Soyad</th>
            <th>Mevcut Bib Numarası</th>
            <th>Yeni Bib Numarası</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                <td><?php echo htmlspecialchars($row['second_name']); ?></td>
                <td><?php echo $row['Bib']; ?></td>
                <td>
                    <input type="hidden" name="registration_ids[]" value="<?php echo $row['id']; ?>">
                    <input type="text" name="new_bib_numbers[]" value=""> <!-- Burayı boş bırakıyoruz -->
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <input type="hidden" name="organization_id" value="<?php echo $organization_id; ?>">
    <button type="submit">Güncelle</button>
</form>
</body>
</html>
