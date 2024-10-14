<?php
include '../db/database.php'; // Veritabanı bağlantısını dahil et

require '../vendor/autoload.php'; // Composer autoload dosyasını dahil et

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// organization_id'yi GET parametresi ile alıyoruz
if (isset($_GET['organization_id'])) {
    $organization_id = intval($_GET['organization_id']);
} else {
    echo "<script>showErrorAlert('Geçersiz organizasyon ID.');</script>";
    exit();
}

// Kayıt Silme İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_registration_id'])) {
    $delete_registration_id = intval($_POST['delete_registration_id']);

    $delete_stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_registration_id);

    if ($delete_stmt->execute()) {
        echo "<script>showSuccessAlert('Kayıt başarıyla silindi!', '/hasgenesis/admin/registrationsManagement.php?organization_id=$organization_id');</script>";
    } else {
        echo "<script>showErrorAlert('Kayıt silinirken bir hata oluştu.');</script>";
    }
}

// Filtreleme ve sıralama işlemleri
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$price_filter = isset($_GET['price']) ? $_GET['price'] : '';
$race_type_filter = isset($_GET['race_type']) ? $_GET['race_type'] : '';
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'id';
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'ASC';

$sql = "SELECT * FROM registrations WHERE organization_id = ?";
$filters = [];
$types = 'i';

// Apply filters for category, price, and race type
if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $filters[] = $category_filter;
    $types .= 's';
}
if (!empty($price_filter)) {
    $sql .= " AND registration_price = ?";
    $filters[] = $price_filter;
    $types .= 'd';
}
if (!empty($race_type_filter)) {
    $sql .= " AND race_type LIKE ?";
    $filters[] = "%$race_type_filter%"; // LIKE operatörü ile filtreleme
    $types .= 's';
}

// Apply sorting
$sql .= " ORDER BY $order_by $order_dir";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, $organization_id, ...$filters);
$stmt->execute();
$result = $stmt->get_result();


// Excel Export Logic
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $query = "SELECT * FROM registrations WHERE organization_id = ?";
    $stmt_export = $conn->prepare($query);
    $stmt_export->bind_param("i", $organization_id);
    $stmt_export->execute();
    $result_export = $stmt_export->get_result();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Adjusting headers to match the provided Excel format
    $sheet->setCellValue('A1', 'Bib');
    $sheet->setCellValue('B1', 'First name');
    $sheet->setCellValue('C1', 'Last name');
    $sheet->setCellValue('D1', 'Category');

    // Populate the data
    $row = 2;
    while ($data = $result_export->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $data['Bib']);
        $sheet->setCellValue('B' . $row, $data['first_name']);
        $sheet->setCellValue('C' . $row, $data['second_name']);
        $sheet->setCellValue('D' . $row, $data['category']);
        $row++;
    }

    // Output the Excel file
    $writer = new Xlsx($spreadsheet);
    $fileName = 'registrations_' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    $writer->save('php://output');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Yönetimi</title>
    <link rel="stylesheet" href="/hasgenesis/admin/admincss/regmanegement.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="/hasgenesis/js/registeralerts.js"></script>
</head>
<body>
<h2>Organization ID: <?php echo $organization_id; ?> Kayıt Yönetimi</h2>

<a href="registrationsManagement.php?action=export&organization_id=<?php echo $organization_id; ?>" class="btn btn-primary">Excel Olarak İndir</a>

<!-- Filtreleme ve Sıralama Formu -->
<div class="filter-sort">
    <form method="GET" action="registrationsManagement.php">
        <input type="hidden" name="organization_id" value="<?php echo $organization_id; ?>">

        <!-- Kategoriye Göre Filtreleme -->
        <label for="category">Kategoriye Göre Filtrele:</label>
        <select name="category" id="category">
            <option value="">Tüm Kategoriler</option>
            <?php
            // Tüm kategorileri dinamik olarak veritabanından almak için:
            $category_query = "SELECT DISTINCT category FROM registrations WHERE organization_id = ?";
            $category_stmt = $conn->prepare($category_query);
            $category_stmt->bind_param("i", $organization_id);
            $category_stmt->execute();
            $category_result = $category_stmt->get_result();
            while ($cat_row = $category_result->fetch_assoc()) {
                $selected = (isset($_GET['category']) && $_GET['category'] == $cat_row['category']) ? 'selected' : '';
                echo "<option value='" . $cat_row['category'] . "' $selected>" . $cat_row['category'] . "</option>";
            }
            ?>
        </select>

        <!-- Fiyata Göre Filtreleme -->
        <label for="price">Fiyata Göre Filtrele:</label>
        <input type="number" name="price" id="price" placeholder="Fiyat" value="<?php echo isset($_GET['price']) ? $_GET['price'] : ''; ?>">

        <!-- Yarış Tipine Göre Filtreleme -->
        <label for="race_type">Yarış Tipine Göre Filtrele:</label>
        <select name="race_type" id="race_type">
            <option value="">Tüm Yarış Tipleri</option>
            <option value="Downhill" <?php echo (isset($_GET['race_type']) && $_GET['race_type'] == 'Downhill') ? 'selected' : ''; ?>>Downhill</option>
            <option value="Enduro" <?php echo (isset($_GET['race_type']) && $_GET['race_type'] == 'Enduro') ? 'selected' : ''; ?>>Enduro</option>
            <option value="Tour" <?php echo (isset($_GET['race_type']) && $_GET['race_type'] == 'Tour') ? 'selected' : ''; ?>>Tour</option>
            <option value="Ulumega" <?php echo (isset($_GET['race_type']) && $_GET['race_type'] == 'Ulumega') ? 'selected' : ''; ?>>Ulumega</option>
            <option value="E_bike" <?php echo (isset($_GET['race_type']) && $_GET['race_type'] == 'E_bike') ? 'selected' : ''; ?>>E_bike</option>
        </select>

       <!-- Sıralama Kriteri -->
       <label for="order_by">Sıralama Kriteri:</label>
        <select name="order_by" id="order_by">
            <option value="id" <?php echo (isset($_GET['order_by']) && $_GET['order_by'] == 'id') ? 'selected' : ''; ?>>ID</option>
            <option value="registration_price" <?php echo (isset($_GET['order_by']) && $_GET['order_by'] == 'registration_price') ? 'selected' : ''; ?>>Fiyat</option>
        </select>

        <!-- Sıralama Yönü -->
        <label for="order_dir">Sıralama Yönü:</label>
        <select name="order_dir" id="order_dir">
            <option value="ASC" <?php echo (isset($_GET['order_dir']) && $_GET['order_dir'] == 'ASC') ? 'selected' : ''; ?>>Artan</option>
            <option value="DESC" <?php echo (isset($_GET['order_dir']) && $_GET['order_dir'] == 'DESC') ? 'selected' : ''; ?>>Azalan</option>
        </select>

        <!-- Filtrele ve Sırala Butonu -->
        <button type="submit">Filtrele ve Sırala</button>
    </form>
</div>

<!-- Tablo -->
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Bib</th>
                <th>İsim</th>
                <th>Soyisim</th>
                <th>Kategori</th>
                <th>Yarış Tipi</th>
                <th>Fiyat</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['Bib'] . "</td>";
                    echo "<td>" . $row['first_name'] . "</td>";
                    echo "<td>" . $row['second_name'] . "</td>";
                    echo "<td>" . $row['category'] . "</td>";
                    echo "<td>" . $row['race_type'] . "</td>";
                    echo "<td>" . $row['registration_price'] . "</td>";
                    echo "<td>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='delete_registration_id' value='" . $row['id'] . "'>
                                <button type='submit' onclick='return confirm(\"Bu kaydı silmek istediğinizden emin misiniz?\");'>Sil</button>
                            </form>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7'>Kayıt bulunamadı.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>
