<?php
include 'sidebar.php';
include '../db/database.php'; // Veritabanı bağlantısını dahil et

// Silme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_ids'])) {
    $delete_ids = $_POST['delete_ids']; // Silinecek mesajların ID'leri
    if (!empty($delete_ids)) {
        $ids_to_delete = implode(',', array_map('intval', $delete_ids)); // ID'leri birleştir
        $delete_query = "DELETE FROM communication WHERE id IN ($ids_to_delete)";
        $conn->query($delete_query); // $db yerine $conn kullanıldı
    }
}

// Filtreleme seçeneklerini kontrol et
$is_user_filter = isset($_POST['is_user']) ? $_POST['is_user'] : null;
$topic_filter = isset($_POST['topic']) ? $_POST['topic'] : null;

// Sayfa numarasını al (Eğer tanımlı değilse varsayılan olarak 1)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Sayfa başına kaç mesaj gösterileceğini belirle
$messagesPerPage = 10; // Her sayfada 10 mesaj gösterilecek

// Toplam mesaj sayısını bul
$totalMessagesQuery = "SELECT COUNT(*) as total FROM communication";
$totalMessagesResult = $conn->query($totalMessagesQuery);
$totalMessages = $totalMessagesResult->fetch_assoc()['total'];

// Toplam sayfa sayısını hesapla
$totalPages = ceil($totalMessages / $messagesPerPage);

// Eğer geçersiz bir sayfa numarası gelirse varsayılan olarak 1. sayfaya git
if ($page < 1) $page = 1;
if ($page > $totalPages) $page = $totalPages;

// Hangi mesajları alacağını belirle (OFFSET ve LIMIT kullanarak)
$offset = ($page - 1) * $messagesPerPage;
$query = "SELECT * FROM communication";
$filters = [];

if ($is_user_filter !== null) {
    if ($is_user_filter == 1) {
        $filters[] = "is_user = 1"; // Kullanıcı mesajları
    } elseif ($is_user_filter == 0) {
        $filters[] = "is_user = 0"; // Kullanıcı olmayan mesajlar
    }
}

if ($topic_filter !== null && $topic_filter !== "") {
    $filters[] = "topic = '$topic_filter'"; // Konuya göre filtreleme
}

if (!empty($filters)) {
    $query .= " WHERE " . implode(' AND ', $filters);
}

// Sayfalama eklemek için sorguya LIMIT ve OFFSET ekle
$query .= " LIMIT $messagesPerPage OFFSET $offset";

// Mesajları al
$result = $conn->query($query); // $db yerine $conn kullanıldı

// Konuları al (sadece bir kere sorgulayıp kullanmak için)
$topics_result = $conn->query("SELECT DISTINCT topic FROM communication");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <title>İletişim Yönetimi</title>
    <link rel="stylesheet" href="admincss/communication-management.css"> <!-- CSS dosyası -->
</head>
<body>

<div class="content">
    <h2>İletişim Mesajları</h2>

    <form method="POST" action="">
        <label>
            <input type="radio" name="is_user" value="1" <?php echo $is_user_filter == 1 ? 'checked' : ''; ?>> Kullanıcı Mesajları
        </label>
        <label>
            <input type="radio" name="is_user" value="0" <?php echo $is_user_filter == 0 ? 'checked' : ''; ?>> Kullanıcı Olmayan Mesajlar
        </label>
        <label>
            <input type="radio" name="is_user" value="" <?php echo $is_user_filter === null ? 'checked' : ''; ?>> Hepsini Göster
        </label>
        <br><br>
        <label for="topic">Konu:</label>
        <select name="topic" id="topic">
            <option value="">Tümü</option>
            <?php while ($topic = $topics_result->fetch_assoc()) { ?>
                <option value="<?php echo $topic['topic']; ?>" <?php echo $topic_filter == $topic['topic'] ? 'selected' : ''; ?>>
                    <?php echo $topic['topic']; ?>
                </option>
            <?php } ?>
        </select>
        <input type="submit" value="Filtrele">
    </form>

    <form method="POST" action="">
        <table>
            <thead>
                <tr>
                    <th>Seç</th>
                    <th>Ad</th>
                    <th>Soyad</th>
                    <th>Şirket</th>
                    <th>Konu</th>
                    <th>Telefon Numarası</th>
                    <th>E-posta</th>
                    <th>Mesaj</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><input type="checkbox" name="delete_ids[]" value="<?php echo $row['id']; ?>"></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['surname']; ?></td>
                        <td><?php echo $row['company']; ?></td>
                        <td><?php echo $row['topic']; ?></td>
                        <td><?php echo $row['phone_number']; ?></td>
                        <td><?php echo $row['mail']; ?></td>
                        <td>
                            <?php 
                            // Mesajın sadece bir kısmını göster
                            echo mb_strimwidth($row['text'], 0, 50, '...'); 
                            ?>
                        </td>
                        <td><?php echo $row['created_at']; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <input type="submit-delete" value="Seçilenleri Sil" onclick="return confirm('Seçilen mesajları silmek istediğinize emin misiniz?');">
    </form>

    <div class="pagination-container">
        <!-- Sayfalama Bağlantıları -->
        <?php if ($page > 1): ?>
            <a href="communication-management.php?page=<?php echo $page - 1; ?>" class="btn btn-outline-primary pagination-link">Önceki</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="communication-management.php?page=<?php echo $i; ?>" class="btn btn-outline-primary pagination-link <?php if ($i == $page) echo 'active'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="communication-management.php?page=<?php echo $page + 1; ?>" class="btn btn-outline-primary pagination-link">Sonraki</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
