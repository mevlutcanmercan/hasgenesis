<?php
include '../db/database.php'; // Veritabanı bağlantısını dahil et
include 'sidebar.php';
require '../vendor/autoload.php'; // Composer autoload dosyasını dahil et

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer dosyalarını dahil et
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// organization_id'yi GET parametresi ile alıyoruz
if (isset($_GET['organization_id'])) {
    $organization_id = intval($_GET['organization_id']);
} else {
    echo "<script>showErrorAlert('Geçersiz organizasyon ID.');</script>";
    exit();
}

// Organizasyonun ismini alacak fonksiyon
function checkOrganizationName($conn, $organization_id) {
    $sql = "SELECT name FROM organizations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['name'];
    } else {
        return "Bilinmeyen Organizasyon";
    }
}
$organization_name = checkOrganizationName($conn, $organization_id);

// Kullanıcının mail adresini almak için fonksiyon
function getUserEmail($conn, $firstName, $lastName) {
    $sql = "SELECT mail_users FROM users WHERE name_users = ? AND surname_users = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $firstName, $lastName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['mail_users'];
    } else {
        return null; // E-posta bulunamazsa null döner
    }
}

// Yarış tiplerini almak için fonksiyon
function getOpenRaceTypes($conn, $organization_id) {
    $sql = "SELECT downhill, enduro, tour, ulumega, e_bike FROM organizations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

$raceTypes = getOpenRaceTypes($conn, $organization_id);

// Excel İndirme İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'export' && isset($_GET['race_type'])) {
    $race_type = $_GET['race_type'];

    // Kayıtları almak için SQL sorgusu
    $sql = "SELECT * FROM registrations WHERE organization_id = ? AND race_type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $organization_id, $race_type);
    $stmt->execute();
    $result = $stmt->get_result();

    // Yaş kategorilerini almak için SQL sorgusu
    $sql_age_category = "SELECT * FROM age_category WHERE organization_id = ? AND race_type = ?";
    $stmt_age = $conn->prepare($sql_age_category);
    $stmt_age->bind_param("is", $organization_id, $race_type);
    $stmt_age->execute();
    $age_category_result = $stmt_age->get_result();
    $age_categories = $age_category_result->fetch_assoc();

    // Yeni bir Spreadsheet oluştur
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Başlık satırlarını ekle
    $sheet->setCellValue('A1', 'Bib');
    $sheet->setCellValue('B1', 'Name');
    $sheet->setCellValue('C1', 'First Name');
    $sheet->setCellValue('D1', 'Last Name');
    $sheet->setCellValue('E1', 'Category');

    $rowNumber = 2; // Başlık satırından sonra başlıyoruz

    // Kayıtları döngüye al
    while ($row = $result->fetch_assoc()) {
        // Kayıt bilgilerini al
        $bib = $row['Bib'];
        $firstName = $row['first_name'];
        $lastName = $row['second_name'];

        // Kategori bilgisi
        $category = '';
        if ($row['dh_kategori']) {
            $category = $row['dh_kategori'] . ' ' . $age_categories['junior'];
        } elseif ($row['end_kategori']) {
            $category = $row['end_kategori'] . ' ' . $age_categories['elite'];
        } elseif ($row['ulumega_kategori']) {
            $category = $row['ulumega_kategori'] . ' ' . $age_categories['master_a'];
        } elseif ($row['tour_kategori']) {
            $category = $row['tour_kategori'] . ' ' . $age_categories['master_b'];
        } elseif ($row['ebike_kategori']) {
            $category = $row['ebike_kategori'] . ' ' . $age_categories['kadinlar'];
        }

        // Verileri Excel'e ekle
        $sheet->setCellValue('A' . $rowNumber, $bib);
        $sheet->setCellValue('B' . $rowNumber, $firstName . ' ' . $lastName);
        $sheet->setCellValue('C' . $rowNumber, $firstName);
        $sheet->setCellValue('D' . $rowNumber, $lastName);
        $sheet->setCellValue('E' . $rowNumber, $category);
        $rowNumber++;
    }

    // Excel dosyasını indirme işlemi
    $writer = new Xlsx($spreadsheet);
    $filename = 'registrations_' . $race_type . '_' . date('Y-m-d') . '.xlsx';

    // Header bilgilerini ayarla
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Tamponu temizle
    ob_clean();
    flush();

    // Dosyayı yaz
    $writer->save('php://output');
    exit();
}

// Kayıt Silme İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_registration_id'])) {
    // Delete registration logic...
}

// Kayıt Onaylama ve Reddetme İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registration_id']) && isset($_POST['approval_status'])) {
    $registration_id = intval($_POST['registration_id']);
    $approval_status = intval($_POST['approval_status']); // 1: Onaylı, 0: Onaysız

    // Onay durumu güncelleme sorgusu
    $sql = "UPDATE registrations SET approval_status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $approval_status, $registration_id);

    if ($stmt->execute()) {
        // Başarılı güncelleme durumunda
        echo "<script>showSuccessAlert('Kayıt onay durumu güncellendi.');</script>";
    } else {
        // Hata durumunda
        echo "<script>showErrorAlert('Güncelleme sırasında hata oluştu.');</script>";
    }
}

// Filtreleme ve sıralama işlemleri
$race_type_filter = isset($_GET['race_type']) ? $_GET['race_type'] : '';
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'id';
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'ASC';

$sql = "SELECT * FROM registrations WHERE organization_id = ?";
$filters = [];
$types = 'i';

// Yarış tipi filtrasyonu
if (!empty($race_type_filter)) {
    $sql .= " AND race_type LIKE ?";
    $filters[] = "%$race_type_filter%"; // Virgülle ayrılmış değerler için LIKE kullanıyoruz
    $types .= 's';
}

// Sıralama uygulama
$sql .= " ORDER BY $order_by $order_dir";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, $organization_id, ...$filters);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <title>Kayıt Yönetimi</title>
    <link rel="stylesheet" href="admincss/regmanegement.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="../js/registeralerts.js"></script>
</head>
<body>
<h2><?php echo $organization_name; ?> - Kayıt Yönetimi</h2>

<!-- Filtreleme ve Sıralama Formu -->
<div class="filter-sort">
    <form method="GET" action="registrationsmanagement" id="filterForm">
        <input type="hidden" name="organization_id" value="<?php echo $organization_id; ?>">

        <!-- Yarış tipi combobox'ı -->
        <select name="race_type" onchange="this.form.submit()">
            <option value="">Tüm Yarış Tipleri</option>
            <?php if ($raceTypes['downhill']) echo '<option value="downhill"' . ($race_type_filter === 'downhill' ? ' selected' : '') . '>Downhill</option>'; ?>
            <?php if ($raceTypes['enduro']) echo '<option value="enduro"' . ($race_type_filter === 'enduro' ? ' selected' : '') . '>Enduro</option>'; ?>
            <?php if ($raceTypes['tour']) echo '<option value="tour"' . ($race_type_filter === 'tour' ? ' selected' : '') . '>Tour</option>'; ?>
            <?php if ($raceTypes['ulumega']) echo '<option value="ulumega"' . ($race_type_filter === 'ulumega' ? ' selected' : '') . '>Ulumega</option>'; ?>
            <?php if ($raceTypes['e_bike']) echo '<option value="e_bike"' . ($race_type_filter === 'e_bike' ? ' selected' : '') . '>E-Bike</option>'; ?>
        </select>
        
        <!-- Excel İndir Butonu -->
        <button type="button" class="btn btn-primary" onclick="checkRaceTypeAndDownload()">Excel İndir</button>


        <a href="editBibNumbers?organization_id=<?php echo $organization_id; ?>" class="btn btn-primary">Bib</a>
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
                <th>DH Kategori</th>
                <th>End Kategori</th>
                <th>Ulumega Kategori</th>
                <th>Tour Kategori</th>
                <th>Ebike Kategori</th>
                <th>Yarış Tipi</th>
                <th>Fiyat</th>
                <th>Feragatname</th>
                <th>Ücret Belgesi</th>
                <th>E-Mail</th>
                <th>Onay Durumu</th>
                <th>İşlem</th>
                <th>Sil</th>
            </tr>
        </thead>
        <tbody>
        <?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Kullanıcı mail adresini çek
        $mail = getUserEmail($conn, $row['first_name'], $row['second_name']);

        echo "<tr id='row-{$row['id']}'>";
        echo "<td>" . $row['Bib'] . "</td>";
        echo "<td>" . $row['first_name'] . "</td>";
        echo "<td>" . $row['second_name'] . "</td>";

        // Yeni kategoriler için verileri ekle
        echo "<td>" . ($row['dh_kategori'] ? $row['dh_kategori'] : '-') . "</td>";
        echo "<td>" . ($row['end_kategori'] ? $row['end_kategori'] : '-') . "</td>";
        echo "<td>" . ($row['ulumega_kategori'] ? $row['ulumega_kategori'] : '-') . "</td>";
        echo "<td>" . ($row['tour_kategori'] ? $row['tour_kategori'] : '-') . "</td>";
        echo "<td>" . ($row['ebike_kategori'] ? $row['ebike_kategori'] : '-') . "</td>";
        
        echo "<td>" . $row['race_type'] . "</td>";
        echo "<td>" . $row['registration_price'] . "</td>";
        
        // Feragatname ve Ücret Belgesi sütunları
        echo "<td>" . ($row['feragatname'] ? "<a href='../documents/feragatname/" . $row['feragatname'] . "' target='_blank'>Feragatname</a>" : "-") . "</td>";
        echo "<td>" . ($row['price_document'] ? "<a href='../documents/receipt/" . $row['price_document'] . "' target='_blank'>Ücret Belgesi</a>" : "-") . "</td>";

        // Mail adresi ve pop-up
        echo "<td>" . ($mail ? "<a href='#' onclick='sendMail(\"$mail\")'>$mail</a>" : "-") . "</td>";

        // Onay durumu
        $statusClass = $row['approval_status'] ? "approved" : "rejected";
        echo "<td class='$statusClass' id='status-{$row['id']}'>" . ($row['approval_status'] ? "Onaylı" : "Onaysız") . "</td>";
        
        // Onaylama ve reddetme işlemleri
        echo "<td><form method='POST' action='registrationsmanagement.php?organization_id=$organization_id' style='display:inline;'>";
        echo "<input type='hidden' name='registration_id' value='{$row['id']}'>";
        echo "<input type='hidden' name='approval_status' value='" . ($row['approval_status'] ? 0 : 1) . "'>";
        echo "<button type='submit' class='" . ($row['approval_status'] ? "reject-btn" : "approve-btn") . "'>" . ($row['approval_status'] ? "Reddet" : "Onayla") . "</button>";
        echo "</form></td>";

        // Silme işlemi
        echo "<td>
        <form id='deleteForm-{$row['id']}' method='POST' action=''>
            <input type='hidden' name='delete_registration_id' value='{$row['id']}'>
            <button type='button' onclick='confirmDelete({$row['id']})' class='delete-btn'>Sil</button>
        </form>
        </td>";

        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='17'>Kayıt bulunamadı.</td></tr>";
}
?>
        </tbody>
    </table>
</div>

</body>
</html>

<script>
    function checkRaceTypeAndDownload() {
            var raceType = document.querySelector('select[name="race_type"]').value;
            if (!raceType) {
                Swal.fire({
                    title: 'Uyarı',
                    text: "Kayıtları indirmek için lütfen yarış tipi seçiniz.",
                    icon: 'warning'
                });
            } else {
                window.location.href = '?organization_id=<?php echo $organization_id; ?>&race_type=' + raceType + '&action=export';
            }
        }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Silmek istediğinize emin misiniz?',
            text: "Bu işlem geri alınamaz!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'Hayır, iptal et!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm-' + id).submit();
            }
        });
    }
</script>

