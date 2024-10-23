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

// Silinen kayıt için e-posta gönderme fonksiyonu
function sendEmailOnDelete($email, $firstName, $lastName) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'mail.hasgenesis.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@hasgenesis.com';
        $mail->Password = 'QVVXaWsZ*b9S';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Gönderen bilgisi
        $mail->setFrom('info@hasgenesis.com', 'Has Genesis');
        $mail->addAddress($email);  // Alıcı e-posta adresi
        $mail->CharSet = 'UTF-8';   // Türkçe karakter desteği

        // İçerik
        $mail->isHTML(true);
        $mail->Subject = 'Kaydınız Gerekliliklere Uygun Değildir';
        $mail->Body    = 'Merhaba ' . $firstName . ' ' . $lastName . ',<br><br>' . 'Kaydınız gerekliliklere uygun olmadığı için iptal edilmiştir.';

        $mail->send();
    } catch (Exception $e) {
        // Eğer mail gönderimi başarısız olursa, bunu burada yönetebilirsiniz.
        error_log("Mail gönderim hatası: {$mail->ErrorInfo}");
    }
}

// Kayıt Silme İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_registration_id'])) {
    $delete_registration_id = intval($_POST['delete_registration_id']);

    // Silmeden önce kayıt sahibinin mailini almak için sorgu
    $stmt_user = $conn->prepare("SELECT u.mail_users, r.first_name, r.second_name FROM users u 
        JOIN user_registrations ur ON u.id_users = ur.user_id 
        JOIN registrations r ON ur.registration_id = r.id 
        WHERE r.id = ?");
    $stmt_user->bind_param("i", $delete_registration_id);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();

    if ($user_row = $user_result->fetch_assoc()) {
        $email = $user_row['mail_users'];
        $firstName = $user_row['first_name'];
        $lastName = $user_row['second_name'];
    } else {
        echo "<script>showErrorAlert('Kullanıcı bilgileri alınırken bir hata oluştu.');</script>";
        exit();
    }

    $delete_stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_registration_id);

    if ($delete_stmt->execute()) {
        // Kayıt silindikten sonra e-posta gönder
        sendEmailOnDelete($email, $firstName, $lastName);
        echo "<script>showSuccessAlert('Kayıt başarıyla silindi!', 'admin/registrationsmanagement.php?organization_id=$organization_id');</script>";
    } else {
        echo "<script>showErrorAlert('Kayıt silinirken bir hata oluştu.');</script>";
    }
}


// Kayıt Onaylama ve Reddetme İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registration_id']) && isset($_POST['approval_status'])) {
    $registration_id = intval($_POST['registration_id']);
    $approval_status = intval($_POST['approval_status']);

    $update_stmt = $conn->prepare("UPDATE registrations SET approval_status = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $approval_status, $registration_id);

    if ($update_stmt->execute()) {
        echo "<script>showSuccessAlert('Kayıt durumu güncellendi!');</script>";
    } else {
        echo "<script>showErrorAlert('Kayıt durumu güncellenirken bir hata oluştu.');</script>";
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


// Kategori dönüşüm tablosu
$category_map = [
    'JUNIOR' => 'JUNIOR 14-21',
    'ELITLER' => 'ELITLER 22-35',
    'MASTER A' => 'MASTER A 36-45',
    'MASTER B' => 'MASTER B 46-...',
    'KADINLAR' => 'KADINLAR 17-...',
    'E-BIKE' => 'E-BIKE 17-...',
];

// Excel Export Logic
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    // Çıktı tamponunu temizle
    ob_clean();
    
    // Veritabanı sorgusu
    $query = "SELECT * FROM registrations WHERE organization_id = ? AND approval_status = 1";
    $stmt_export = $conn->prepare($query);
    $stmt_export->bind_param("i", $organization_id);
    $stmt_export->execute();
    $result_export = $stmt_export->get_result();

    // Excel dosyası oluşturma
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Sınır stilini tanımla
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'], // Siyah renkli ince çizgi
            ],
        ],
    ];

    // Excel başlıklarını ayarla
    $sheet->setCellValue('A1', 'Bib');
    $sheet->setCellValue('B1', 'Name');
    $sheet->setCellValue('C1', 'First Name');
    $sheet->setCellValue('D1', 'Last Name');
    $sheet->setCellValue('E1', 'Category');

    // Verileri tabloya ekle
    $row = 2;
    while ($data = $result_export->fetch_assoc()) {
        // Name sütunu için first_name + second_name birleştir
        $full_name = $data['first_name'] . ' ' . $data['second_name'];
        $sheet->setCellValue('A' . $row, $data['Bib']);
        $sheet->setCellValue('B' . $row, $full_name); // Name sütunu
        $sheet->setCellValue('C' . $row, $data['first_name']);
        $sheet->setCellValue('D' . $row, $data['second_name']);

        // Kategori dönüşümü
        $category = isset($category_map[$data['category']]) ? $category_map[$data['category']] : $data['category'];
        $sheet->setCellValue('E' . $row, $category);

        // Hücrelere sınır ekle
        $sheet->getStyle("A$row:E$row")->applyFromArray($styleArray);

        $row++;
    }

    // Başlıklar için de sınır ekle
    $sheet->getStyle('A1:E1')->applyFromArray($styleArray);

        // Sütun genişliklerini ayarla
        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

    // Excel dosyasını indirilmeye hazırla
    $writer = new Xlsx($spreadsheet);
    $fileName = 'registrations_' . date('Y-m-d') . '.xlsx';

    // Excel için doğru başlıklar
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    
    // Excel dosyasını çıktı olarak gönder
    $writer->save('php://output');
    exit();
}


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
<script>
    function confirmDelete(registrationId) {
        // SweetAlert ile onay mesajı
        Swal.fire({
            title: 'Bu kaydı silmek istediğinizden emin misiniz?',
            text: "Bu işlem geri alınamaz!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'Hayır'
        }).then((result) => {
            if (result.isConfirmed) {
                // Eğer onaylanırsa, formu submit et
                document.getElementById('deleteForm-' + registrationId).submit();
            }
        })
    }
</script>
<body>
<h2><?php echo $organization_name; ?> - Kayıt Yönetimi</h2>

<!-- Filtreleme ve Sıralama Formu -->
<div class="filter-sort">
    <form method="GET" action="registrationsmanagement">
        <input type="hidden" name="organization_id" value="<?php echo $organization_id; ?>">

        <!-- Kategoriye Göre Filtreleme -->
        <label for="category">Kategoriye Göre Filtrele:</label>
        <select name="category" id="category">
            <option value="">Tüm Kategoriler</option>
            <!-- Dinamik Kategori Seçenekleri -->
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
        <select name="order_by">
            <option value="Bib" <?php echo (isset($_GET['order_by']) && $_GET['order_by'] == 'Bib') ? 'selected' : ''; ?>>Bib</option>
            <option value="first_name" <?php echo (isset($_GET['order_by']) && $_GET['order_by'] == 'first_name') ? 'selected' : ''; ?>>İsim</option>
            <option value="registration_price" <?php echo (isset($_GET['order_by']) && $_GET['order_by'] == 'registration_price') ? 'selected' : ''; ?>>Fiyat</option>
        </select>

        <!-- Sıralama Yönü -->
        <label for="order_dir">Sıralama Yönü:</label>
        <select name="order_dir">
            <option value="ASC" <?php echo (isset($_GET['order_dir']) && $_GET['order_dir'] == 'ASC') ? 'selected' : ''; ?>>Artan</option>
            <option value="DESC" <?php echo (isset($_GET['order_dir']) && $_GET['order_dir'] == 'DESC') ? 'selected' : ''; ?>>Azalan</option>
        </select>

        <!-- Filtrele ve Sırala Butonu -->
        <button type="submit">Filtrele ve Sırala</button>
        <a href="registrationsmanagement?action=export&organization_id=<?php echo $organization_id; ?>" class="btn btn-primary">Excel İndir </a>
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
                <th>Kategori</th>
                <th>Yarış Tipi</th>
                <th>Fiyat</th>
                <th>Onay Durumu</th>
                <th>Belgeler</th>
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

                // user_registrations tablosu ile user tablosundan mail bilgisi çekme
        $user_stmt = $conn->prepare("SELECT u.mail_users FROM users u JOIN user_registrations ur ON u.id_users = ur.user_id WHERE ur.registration_id = ?");
        $user_stmt->bind_param("i", $row['id']);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user_row = $user_result->fetch_assoc();

        echo "<tr id='row-{$row['id']}'>";
        echo "<td>" . $row['Bib'] . "</td>";
        echo "<td>" . $row['first_name'] . "</td>";
        echo "<td>" . $row['second_name'] . "</td>";
        echo "<td>" . $row['category'] . "</td>";
        echo "<td>" . $row['race_type'] . "</td>";
        echo "<td>" . $row['registration_price'] . "</td>";
        
        // Feragatname ve Ücret Belgesi sütunları
        echo "<td>" . ($row['feragatname'] ? "<a href='../documents/feragatname/" . $row['feragatname'] . "' target='_blank'>Feragatname</a>" : "-") . "</td>";
        echo "<td>" . ($row['price_document'] ? "<a href='../documents/receipt/" . $row['price_document'] . "' target='_blank'>Ücret Belgesi</a>" : "-") . "</td>";

            // Mail adresi ve pop-up
    if ($user_row) {
        $mail = $user_row['mail_users'];
        echo "<td><a href='#' onclick='sendMail(\"$mail\")'>" . $mail . "</a></td>";
    } else {
        echo "<td>-</td>";
    }

        // Onay durumu
        // Onaylı ise yeşil, onaysız ise kırmızı arka plan
        $statusClass = $row['approval_status'] ? "approved" : "rejected";
        echo "<td class='$statusClass' id='status-{$row['id']}'>" . ($row['approval_status'] ? "Onaylı" : "Onaysız") . "</td>";
        echo "<td>";
        
        // Onaylama ve reddetme işlemleri için form ekle
        echo "<form method='POST' action='registrationsmanagement.php?organization_id=$organization_id' style='display:inline;'>";
        echo "<input type='hidden' name='registration_id' value='{$row['id']}'>";
        echo "<input type='hidden' name='approval_status' value='" . ($row['approval_status'] ? 0 : 1) . "'>";
        echo "<button type='submit' class='" . ($row['approval_status'] ? "reject-btn" : "approve-btn") . "'>" . ($row['approval_status'] ? "Reddet" : "Onayla") . "</button>";
        echo "</form>";

            // Silme işlemi
    echo "<td>
    <form id='deleteForm-{$row['id']}' method='POST' action=''>
        <input type='hidden' name='delete_registration_id' value='{$row['id']}'>
        <button type='button' onclick='confirmDelete({$row['id']})' class='delete-btn'>Sil</button>
    </form>
</td>";

echo "</tr>";

        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='10'>Kayıt bulunamadı.</td></tr>";
}
?>


</tbody>
    </table>
</div>

<script>
    function sendMail(mail) {
    Swal.fire({
        title: 'Mail Gönder',
        text: mail + ' adresine mail göndermek istiyor musunuz?',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Gönder',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mail uygulaması açılır
            window.location.href = 'mailto:' + mail;
        }
    })
}
</script>

</body>
</html>
