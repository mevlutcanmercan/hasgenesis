<?php
include '../dB/database.php';
include 'sidebar.php';

// Silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Transaction başlat
    $conn->begin_transaction();

    try {
        // İlişkili verileri child tablolardan sil
        $conn->query("DELETE FROM race_results WHERE user_id = $user_id");
        $conn->query("DELETE FROM user_registrations WHERE user_id = $user_id");
        $conn->query("DELETE FROM cancellations WHERE user_id = $user_id");
        $conn->query("DELETE FROM bicycles WHERE user_id = $user_id");

        // Kullanıcıyı sil
        $conn->query("DELETE FROM users WHERE id_users = $user_id");

        $conn->commit(); // İşlemi onayla

        $status = 'success';
    } catch (Exception $e) {
        $conn->rollback(); // Hata olursa geri al
        $status = 'error';
    }

    // Silme işlemi tamamlandıktan sonra aynı sayfayı yenile
    header("Location: https://localhost/hasgenesis/admin/userManagement.php?status=$status");
    exit(); // Yönlendirmeden sonra betiğin devam etmesini engelle
}
function formatName($name) {
    // Türkçe karakterlere dikkat ederek ismi düzgün formatlamak
    $name = mb_strtolower($name, 'UTF-8'); // Tüm karakterleri küçük yap
    $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8"); // Her kelimenin baş harfini büyük yap
    
    return $name;
}

// Kullanıcıları veritabanından çekme
$query = "SELECT * FROM users WHERE isAdmin = 0";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
    <title>Kullanıcı Yönetimi</title>
    <link rel="stylesheet" href="admincss/userManagement.css"> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert için -->
</head>
<body>

<div class="container">
    <h2>Üye Listesi</h2>
    
    <!-- Silme işlemi sonucu için SweetAlert mesajları -->
    <?php if (isset($status)): ?>
        <script>
            <?php if ($status == 'success'): ?>
                Swal.fire(
                    'Başarılı!',
                    'Kullanıcı başarıyla silindi.',
                    'success'
                )
            <?php elseif ($status == 'error'): ?>
                Swal.fire(
                    'Hata!',
                    'Kullanıcı silinirken bir sorun oluştu.',
                    'error'
                )
            <?php endif; ?>
        </script>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ad</th>
                <th>Soyad</th>
                <th>E-posta</th>
                <th>Telefon</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id_users']; ?></td>
                <td><?php echo formatName($row['name_users']); ?></td>
                <td><?php echo formatName($row['surname_users']); ?></td>
                <td><?php echo htmlspecialchars($row['mail_users']); ?></td>
                <td><?php echo htmlspecialchars($row['telefon']); ?></td>
                <td>
                    <button class="btn btn-danger" onclick="confirmDeletion(<?php echo $row['id_users']; ?>)">Sil</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function confirmDeletion(userId) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu kullanıcıyı ve onunla ilişkili tüm verileri silmek istediğinizden emin misiniz?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'Hayır, iptal!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `userManagement.php?action=delete&id=${userId}`;
        }
    })
}
</script>

</body>
</html>

<?php
$conn->close();
?>