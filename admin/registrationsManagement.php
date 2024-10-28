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

// Excel dışa aktarma işlemi
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    ob_clean();

    // Hata ayarları
    error_reporting(E_ALL);
    ini_set('display_errors', 1); // Hataları görmek için

    // Organizasyon ID'sini al
    $organization_id = intval($_GET['organization_id'] ?? 0); // Organizasyon ID'yi al
    $race_type_filter = $_GET['race_type'] ?? null; // URL'den yarış tipini al

    // Yarış tipine göre sorgu oluştur
    $query = "SELECT * FROM registrations WHERE organization_id = ? AND approval_status = 1";

    if ($race_type_filter) {
        // race_type'i kontrol et
        $query .= " AND race_type LIKE ?";
    }

    // Sorguyu çalıştırma
    $stmt_export = $conn->prepare($query);
    
    if ($race_type_filter) {
        // race_type ile kayıtları filtrele
        $filter = '%' . $race_type_filter . '%'; // LIKE kullanarak filtreleme
        $stmt_export->bind_param("is", $organization_id, $filter);
    } else {
        $stmt_export->bind_param("i", $organization_id);
    }

    $stmt_export->execute();
    $result_export = $stmt_export->get_result();

    // Seçilen yarış tipine ait kayıt yoksa indirmeyi durdur
    if ($result_export->num_rows === 0) {
        echo "<script>alert('Seçilen yarış tipi için kayıt bulunmamaktadır.'); window.location.href = '/hasgenesis/admin/registrationsmanagement?organization_id=$organization_id';</script>";
        exit();
    }

    // Yaş kategorilerini al
    $age_category_query = "SELECT * FROM age_category WHERE organization_id = ? AND race_type = ?";
    $stmt_age_category = $conn->prepare($age_category_query);
    $stmt_age_category->bind_param("is", $organization_id, $race_type_filter);
    $stmt_age_category->execute();
    $age_categories_result = $stmt_age_category->get_result();
    $age_categories = $age_categories_result->fetch_assoc(); // Tek bir sonuç bekliyoruz

    // Excel dosyası oluşturma ve başlık satırlarını ekleme
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
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
        $full_name = trim($data['first_name'] . ' ' . $data['second_name']);
        $sheet->setCellValue('A' . $row, $data['Bib']);
        $sheet->setCellValue('B' . $row, $full_name);
        $sheet->setCellValue('C' . $row, $data['first_name']);
        $sheet->setCellValue('D' . $row, $data['second_name']);

        // Kategori dönüşümü ve yaş aralığını ekleme
        $category = '-';        
        $age_range = '-'; // Varsayılan değer

        // Yarış tipine göre doğru kategori ve yaş aralığını al
        if ($race_type_filter) {
            $category_key = ''; // Kategoriyi tutacak değişken
            switch ($race_type_filter) {
                case 'downhill':
                    $category_key = $data['dh_kategori']; // 'downhill' için kategori al
                    break;
                case 'enduro':
                    $category_key = $data['end_kategori']; // 'enduro' için kategori al
                    break;
                case 'ulumega':
                    $category_key = $data['ulumega_kategori']; // 'ulumega' için kategori al
                    break;
                case 'tour':
                    $category_key = $data['tour_kategori']; // 'tour' için kategori al
                    break;
                case 'e_bike':
                    $category_key = $data['ebike_kategori']; // 'e_bike' için kategori al
                    break;
            }

            // Kategori ve yaş aralığını almak
            if ($age_categories) {
                $age_map = [
                    'junior' => $age_categories['junior'],
                    'elite' => $age_categories['elite'],
                    'master_a' => $age_categories['master_a'],
                    'master_b' => $age_categories['master_b'],
                    'kadinlar' => $age_categories['kadinlar']
                ];

                // Kategori adını küçük harfle kontrol edelim
                $lowercase_category_key = strtolower($category_key);
                if (array_key_exists($lowercase_category_key, $age_map)) {
                    $category = strtoupper($lowercase_category_key); // Kategoriyi büyük harfe çevir
                    $age_range = $age_map[$lowercase_category_key];
                }
            }
        }

        // Kategori ve yaş aralığını birleştir
        $sheet->setCellValue('E' . $row, trim($category . ' ' . $age_range));

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
    $fileName = 'registrations_' . $race_type_filter . '_' . date('Y-m-d') . '.xlsx';

    // Excel için doğru başlıklar
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    header('Expires: 0');

    // Excel dosyasını çıktı olarak gönder
    $writer->save('php://output');
    exit();
}
    

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
            echo "<script>showSuccessAlert('Kayıt başarıyla silindi!', 'admin/registrationsmanagement?organization_id=$organization_id');</script>";
        } else {
            echo "<script>showErrorAlert('Kayıt silinirken bir hata oluştu.');</script>";
        }
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
            echo "<td><form method='POST' action='registrationsmanagement?organization_id=$organization_id' style='display:inline;'>";
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

