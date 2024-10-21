<?php
include '../dB/database.php';
include 'sidebar.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer dosyalarını dahil et
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// İptal taleplerini al
$sql = "SELECT c.id AS cancellation_id, c.reason, c.is_approved, c.registration_id, r.first_name, r.second_name, ur.user_id, r.organization_id, o.name AS organization_name
        FROM cancellations c
        JOIN registrations r ON c.registration_id = r.id
        JOIN user_registrations ur ON r.id = ur.registration_id
        JOIN organizations o ON r.organization_id = o.id
        WHERE c.is_approved = 0";
$result = $conn->query($sql);

// İptal talebini onaylama veya reddetme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cancellation_id = intval($_POST['cancellation_id']);
    $action = $_POST['action'];

    if ($action == 'approve') {
        // Onaylama işlemi: is_approved = 1 ve registrations tablosundan silme
        $conn->begin_transaction();
        try {
            // Kullanıcının e-posta adresini ve organizasyon ismini al
            $stmt = $conn->prepare("SELECT r.organization_id, ur.user_id
                                    FROM registrations r
                                    JOIN user_registrations ur ON r.id = ur.registration_id
                                    WHERE r.id = (SELECT registration_id FROM cancellations WHERE id = ?)");
            $stmt->bind_param("i", $cancellation_id);
            $stmt->execute();
            $stmt->bind_result($organization_id, $user_id);
            $stmt->fetch();
            $stmt->close();

            // Kullanıcının e-posta adresini al
            $email_query = $conn->prepare("SELECT mail_users FROM users WHERE id_users = ?");
            $email_query->bind_param("i", $user_id);
            $email_query->execute();
            $email_query->bind_result($user_email);
            $email_query->fetch();
            $email_query->close();

            // Organizasyon adını almak
            $org_query = $conn->prepare("SELECT name FROM organizations WHERE id = ?");
            $org_query->bind_param("i", $organization_id);
            $org_query->execute();
            $org_query->bind_result($organization_name);
            $org_query->fetch();
            $org_query->close();

            // cancellations tablosunda onayla
            $stmt = $conn->prepare("UPDATE cancellations SET is_approved = 1 WHERE id = ?");
            $stmt->bind_param("i", $cancellation_id);
            $stmt->execute();

            // registrations tablosundan sil
            $stmt2 = $conn->prepare("DELETE FROM registrations WHERE id = ?");
            $stmt2->bind_param("i", $_POST['registration_id']);
            $stmt2->execute();

            // E-posta gönderimi
            $mail = new PHPMailer(true);
            try {
                // SMTP ayarları
                $mail->isSMTP();
                $mail->Host = 'mail.hasgenesis.com'; // Giden posta sunucusu
                $mail->SMTPAuth = true;
                $mail->Username = 'info@hasgenesis.com'; // Gönderen e-posta
                $mail->Password = 'QVVXaWsZ*b9S'; // E-posta hesabının şifresi
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL kullanımı
                $mail->Port = 465; // SSL portu

                // Gönderen bilgileri
                $mail->setFrom('info@hasgenesis.com', 'Has Genesis'); // Gönderen adı
                $mail->addAddress($user_email); // Alıcı e-posta

                // E-posta içeriği
                $mail->Subject = 'İptal Talebiniz Hk.';
                $mail->Body = "Merhaba,\n\n{$organization_name} organizasyonuna ait iptal talebiniz onaylanmıştır ve kaydınız silinmiştir! Eğer kendi banka hesabınızdan havale yapmadıysanız, iletişim kısmından bize 'ücret iadesi hk.' konusunu seçerek ücreti gönderdiğiniz hesabın bilgilerini yazınız. Ücret iadeniz 1 hafta içerisinde hesabınıza yatırılacaktır.";

                // Karakter setini ayarlayın
                $mail->CharSet = 'UTF-8'; // UTF-8 karakter seti kullanımı

                // E-posta gönderimi
                $mail->send();
            } catch (Exception $e) {
                echo "E-posta gönderilemedi. Hata: " . $mail->ErrorInfo;
            }

            $conn->commit();

            // İşlem tamamlandıktan sonra yönlendirme
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=approved");
            exit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            echo "Hata: " . $exception->getMessage();  // Detaylı hata mesajı gösteriliyor
        }
    } elseif ($action == 'deny') {
        // Reddetme işlemi: is_approved = 2
        $conn->begin_transaction();
        try {
            // cancellations tablosunda reddet
            $stmt = $conn->prepare("UPDATE cancellations SET is_approved = 2 WHERE id = ?");
            $stmt->bind_param("i", $cancellation_id);
            $stmt->execute();

            // Kullanıcının e-posta adresini almak
            $stmt = $conn->prepare("SELECT ur.user_id, u.mail_users, r.organization_id
                                    FROM cancellations c
                                    JOIN registrations r ON c.registration_id = r.id
                                    JOIN user_registrations ur ON r.id = ur.registration_id
                                    JOIN users u ON ur.user_id = u.id_users
                                    WHERE c.id = ?");
            $stmt->bind_param("i", $cancellation_id);
            $stmt->execute();
            $stmt->bind_result($user_id, $user_email, $organization_id);
            $stmt->fetch();
            $stmt->close();

            // Organizasyon adını almak
            $org_query = $conn->prepare("SELECT name FROM organizations WHERE id = ?");
            $org_query->bind_param("i", $organization_id);
            $org_query->execute();
            $org_query->bind_result($organization_name);
            $org_query->fetch();
            $org_query->close();

            // E-posta gönderimi
            $mail = new PHPMailer(true);
            try {
                // SMTP ayarları
                $mail->isSMTP();
                $mail->Host = 'mail.hasgenesis.com'; // Giden posta sunucusu
                $mail->SMTPAuth = true;
                $mail->Username = 'info@hasgenesis.com'; // Gönderen e-posta
                $mail->Password = 'QVVXaWsZ*b9S'; // E-posta hesabının şifresi
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL kullanımı
                $mail->Port = 465; // SSL portu

                // Gönderen bilgileri
                $mail->setFrom('info@hasgenesis.com', 'Has Genesis'); // Gönderen adı
                $mail->addAddress($user_email); // Alıcı e-posta

                // E-posta içeriği
                $mail->Subject = 'İptal Talebiniz Hk.';
                $mail->Body = "Merhaba,\n\n{$organization_name} organizasyonuna ait iptal talebiniz reddedilmiştir. Lütfen iletişim kurarak daha fazla bilgi alın.";

                // Karakter setini ayarlayın
                $mail->CharSet = 'UTF-8'; // UTF-8 karakter seti kullanımı

                // E-posta gönderimi
                $mail->send();
            } catch (Exception $e) {
                echo "E-posta gönderilemedi. Hata: " . $mail->ErrorInfo;
            }

            $conn->commit();

            // İşlem tamamlandıktan sonra yönlendirme
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=denied");
            exit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            echo "Hata: " . $exception->getMessage();  // Detaylı hata mesajı gösteriliyor
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İptal Talepleri - Admin Panel</title>
    <link rel="stylesheet" href="admincss/admin-cancellation.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="cancellation-container">
        <h2>İptal Talepleri</h2>
        <table>
            <thead>
                <tr>
                    <th>İsim Soyisim</th>
                    <th>Organizasyon</th>
                    <th>İptal Sebebi</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['second_name']); ?></td>
            <td><?php echo htmlspecialchars($row['organization_name']); ?></td>
            <td class="reason-cell"><?php echo htmlspecialchars($row['reason']); ?></td> <!-- İptal sebebi hücresi için özel class -->
            <td>Beklemede</td>
            <td>
                <form action="admin_cancellation.php" method="post" id="action-form-<?php echo $row['cancellation_id']; ?>">
                    <input type="hidden" name="cancellation_id" value="<?php echo $row['cancellation_id']; ?>">
                    <input type="hidden" name="registration_id" value="<?php echo $row['registration_id']; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>"> <!-- Kullanıcı ID'sini gizli alan olarak ekle -->
                    <button type="button" class="approve-button" onclick="confirmAction('approve', <?php echo $row['cancellation_id']; ?>)">Onayla</button>
                    <button type="button" class="deny-button" onclick="confirmAction('deny', <?php echo $row['cancellation_id']; ?>)">Reddet</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</tbody>

        </table>
    </div>

    <script>
        function confirmAction(action, cancellation_id) {
            let form = document.getElementById('action-form-' + cancellation_id);
            let actionType = action === 'approve' ? 'Onaylamak' : 'Reddetmek';

            // Eğer 'approve' işlemi ise, ek onay mesajı gösterelim
            if (action === 'approve') {
                Swal.fire({
                    title: 'Kullanıcının kayıtları sistemden silinecektir!',
                    text: "Bu işlemi onaylıyor musunuz?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Evet, sil ve onayla!',
                    cancelButtonText: 'Hayır, iptal et'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Form'a 'action' inputu ekle ve formu gönder
                        let actionInput = document.createElement("input");
                        actionInput.setAttribute("type", "hidden");
                        actionInput.setAttribute("name", "action");
                        actionInput.setAttribute("value", action);
                        form.appendChild(actionInput);
                        form.submit();
                    }
                });
            } else {
                // Eğer işlem 'deny' ise standart işlem
                Swal.fire({
                    title: `${actionType} İstiyor Musunuz?`,
                    text: "Bu işlem geri alınamaz!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Evet, ' + actionType.toLowerCase(),
                    cancelButtonText: 'Hayır'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Form'a 'action' inputu ekle ve formu gönder
                        let actionInput = document.createElement("input");
                        actionInput.setAttribute("type", "hidden");
                        actionInput.setAttribute("name", "action");
                        actionInput.setAttribute("value", action);
                        form.appendChild(actionInput);
                        form.submit();
                    }
                });
            }
        }
    </script>
</body>
</html>


