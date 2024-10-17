<?php
include '../db/database.php';
include 'sidebar.php';

$organization_id = $_GET['organization_id']; // organization_id'yi alın

// Sadece Bib numarası 0 olan kayıtları getirmek için sorgu
$sql = "SELECT id, Bib FROM registrations WHERE Bib = 0 AND organization_id = ?";
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $registration_ids = $_POST['registration_ids'];
    $new_bib_numbers = $_POST['new_bib_numbers'];
    $organization_id = $_POST['organization_id'];
    $update_count = 0; // Kaç tane Bib güncellendiğini takip etmek için

    for ($i = 0; $i < count($registration_ids); $i++) {
        $id = $registration_ids[$i];
        $newBib = $new_bib_numbers[$i];

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
            $updateStmt->bind_param("si", $newBib, $id);
            $updateStmt->execute();
            $update_count++;
        } else {
            // Mevcut Bib numarası varsa hata mesajı oluştur
            $_SESSION['error_message'] = "Bib numarası '$newBib' zaten kayıtlı!";
        }
    }

    // Başarılı güncellemeler için mesaj oluştur
    if ($update_count > 0) {
        $_SESSION['success_message'] = "$update_count kayıt başarıyla güncellendi!";
    }

    // Güncelleme tamamlandığında yönlendirme yap
    header("Location: editBibNumbers.php?organization_id=" . $organization_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <title>Bib Düzenle</title>
    <link rel="stylesheet" href="styles.css">
</head>
<style>
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
    color: #ffff;
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

tr{
    background-color: #f1f9f9;
}
tr:nth-child(even) {
    background-color: #f9f9f9;
}

tr:hover {
    background-color: #f1f1f1;
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
    <form action="editBibNumbers.php" method="post">
    <table>
            <tr>
                <th>Kayıt ID</th>
                <th>Mevcut Bib Numarası</th>
                <th>Yeni Bib Numarası</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['Bib']; ?></td>
                    <td>
                        <input type="hidden" name="registration_ids[]" value="<?php echo $row['id']; ?>">
                        <input type="text" name="new_bib_numbers[]" value="<?php echo $row['Bib']; ?>">
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <input type="hidden" name="organization_id" value="<?php echo $organization_id; ?>">
        <button type="submit">Güncelle</button>
    </form>
</body>
</html>
