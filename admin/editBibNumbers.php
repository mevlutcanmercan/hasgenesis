<?php
include '../db/database.php'; // Veritabanı bağlantısını dahil et
include 'sidebar.php';

// Bib numaralarını ve gerekli bilgileri almak için sorgu yapın
$sql = "SELECT id, Bib FROM registrations"; // Kendi tablonuza göre düzenleyin
$result = $conn->query($sql);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $registration_ids = $_POST['registration_ids'];
    $new_bib_numbers = $_POST['new_bib_numbers'];

    for ($i = 0; $i < count($registration_ids); $i++) {
        $id = $registration_ids[$i];
        $newBib = $new_bib_numbers[$i];

        // Bib numarasını güncellemek için SQL sorgusu
        $sql = "UPDATE registrations SET Bib = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $newBib, $id);
        $stmt->execute();
    }

    // Güncelleme tamamlandığında yönlendirme yap
    header("Location: editBibNumbers.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bib Numaralarını Düzenle</title>
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

</style>
<body>
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
        <button type="submit">Güncelle</button>
    </form>
</body>
</html>
