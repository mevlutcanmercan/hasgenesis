<?php
include 'sidebar.php';
include '../db/database.php'; // Veritabanı bağlantısını dahil et

// Veritabanındaki sliderları çekelim
$sliders = $conn->query("SELECT * FROM main_page_sliders");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $summary = $_POST['summary'];
    $image_path = $_POST['image_path'];
    $link = $_POST['link'];

    // Karakter sınırını kontrol et
    if (strlen($title) > 30 || strlen($summary) > 30) {
        // Hata mesajını URL'ye ekleyip sayfaya geri yönlendirelim
        header("Location: sliderManagement.php?error=Karakter%20sınırı%20aşıldı!");
        exit();
    } else {
        // Ekleme işlemi
        if (isset($_POST['addSlider'])) {
            $stmt = $conn->prepare("INSERT INTO main_page_sliders (title, summary, image_path, link) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $title, $summary, $image_path, $link);

            if ($stmt->execute()) {
                // Başarılı mesajı
                header("Location: sliderManagement.php?success=Slider%20başarıyla%20eklendi!");
            } else {
                // Hata mesajı
                header("Location: sliderManagement.php?error=Slider%20eklenirken%20bir%20hata%20oluştu!");
            }
            $stmt->close();
            exit();
        }
    }
}

// Slider silme işlemi
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM main_page_sliders WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    // Silme işlemi sonrası başarılı mesaj
    header("Location: sliderManagement.php?success=Slider%20başarıyla%20silindi!");
    exit();
}

// Slider güncelleme işlemi
if (isset($_POST['updateSlider'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $summary = $_POST['summary'];
    $image_path = $_POST['image_path'];
    $link = $_POST['link'];

    $stmt = $conn->prepare("UPDATE main_page_sliders SET title = ?, summary = ?, image_path = ?, link = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $summary, $image_path, $link, $id);
    $stmt->execute();
    // Güncelleme işlemi sonrası başarılı mesaj
    header("Location: sliderManagement.php?success=Slider%20başarıyla%20güncellendi!");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slider Yönetimi</title>
    <link rel="stylesheet" href="admincss/sliderManagement.css"> <!-- CSS Dosyası -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // URL'deki parametreleri kontrol et ve SweetAlert ile göster
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('success')) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: urlParams.get('success'),
                confirmButtonText: 'Tamam'
            });
        }
        if (urlParams.has('error')) {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: urlParams.get('error'),
                confirmButtonText: 'Tamam'
            });
        }
    });
</script>
</head>
<body>
<div class="container">
    <h1>Slider Yönetimi</h1>
    
    <!-- Slider Ekleme Formu -->
    <form action="sliderManagement.php" method="POST" class="form">
        <h2>Yeni Slider Ekle</h2>
        <input type="text" name="title" placeholder="Başlık" required>
        <input type="text" name="summary" placeholder="Özet" required>
        <input type="text" name="image_path" placeholder="Resim Yolu" required>
        <input type="text" name="link" placeholder="Bağlantı" required>
        <button type="submit" name="addSlider">Slider Ekle</button>
    </form>

    <!-- Var Olan Sliderlar -->
    <h2>Mevcut Sliderlar</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Başlık</th>
            <th>Özet</th>
            <th>Resim Yolu</th>
            <th>Bağlantı</th>
            <th>İşlem</th>
        </tr>

        <?php while ($row = $sliders->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['title']; ?></td>
            <td><?php echo $row['summary']; ?></td>
            <td><?php echo $row['image_path']; ?></td>
            <td><?php echo $row['link']; ?></td>
            <td>
                <a href="?edit=<?php echo $row['id']; ?>" class="btn">Düzenle</a>
                <a href="?delete=<?php echo $row['id']; ?>" class="btn delete-btn">Sil</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- Düzenleme Formu -->
    <?php if (isset($_GET['edit'])): 
        $id = $_GET['edit'];
        $slider = $conn->query("SELECT * FROM main_page_sliders WHERE id = $id")->fetch_assoc();
    ?>
    <form action="sliderManagement.php" method="POST" class="form">
        <h2>Slider Düzenle</h2>
        <input type="hidden" name="id" value="<?php echo $slider['id']; ?>">
        <input type="text" name="title" value="<?php echo $slider['title']; ?>" required>
        <input type="text" name="summary" value="<?php echo $slider['summary']; ?>" required>
        <input type="text" name="image_path" value="<?php echo $slider['image_path']; ?>" required>
        <input type="text" name="link" value="<?php echo $slider['link']; ?>" required>
        <button type="submit" name="updateSlider">Slider Güncelle</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>