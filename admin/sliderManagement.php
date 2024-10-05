<?php
include 'sidebar.php';
include '../db/database.php'; // Veritabanı bağlantısını dahil et

// Veritabanındaki sliderları çekelim
$sliders = $conn->query("SELECT * FROM main_page_sliders");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $summary = $_POST['summary'];
    $link = $_POST['link'];
    
    // Resim yükleme
    $imagePath = null;
    if (isset($_FILES['slider_image']) && $_FILES['slider_image']['error'] == UPLOAD_ERR_OK) {
        $targetDir = '../images/'; // Resimlerin yükleneceği klasör
        $targetFile = $targetDir . basename($_FILES['slider_image']['name']);
        move_uploaded_file($_FILES['slider_image']['tmp_name'], $targetFile);
        $imagePath = 'images/' . basename($_FILES['slider_image']['name']); // Veritabanına kaydedilecek yol
    }

    // Karakter sınırını kontrol et
    if (strlen($title) > 30 || strlen($summary) > 30) {
        header("Location: sliderManagement.php?error=Karakter%20sınırı%20aşıldı!");
        exit();
    } else {
        if (isset($_POST['addSlider']) && $imagePath !== null) {
            $stmt = $conn->prepare("INSERT INTO main_page_sliders (title, summary, image_path, link) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $title, $summary, $imagePath, $link);

            if ($stmt->execute()) {
                header("Location: sliderManagement.php?success=Slider%20başarıyla%20eklendi!");
            } else {
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
    $link = $_POST['link'];
    
    // Mevcut resmi kullan eğer yeni resim yüklenmemişse
    $imagePath = $_POST['current_image_path']; // Varsayılan olarak mevcut resim
    if (isset($_FILES['slider_image']) && $_FILES['slider_image']['error'] == UPLOAD_ERR_OK) {
        // Mevcut resmi sil
        if (file_exists('../' . $imagePath)) {
            unlink('../' . $imagePath);
        }
        
        // Yeni resmi yükle
        $targetDir = '../images/';
        $targetFile = $targetDir . basename($_FILES['slider_image']['name']);
        move_uploaded_file($_FILES['slider_image']['tmp_name'], $targetFile);
        $imagePath = 'images/' . basename($_FILES['slider_image']['name']); // Yeni resim yolu
    }

    $stmt = $conn->prepare("UPDATE main_page_sliders SET title = ?, summary = ?, image_path = ?, link = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $summary, $imagePath, $link, $id);
    $stmt->execute();
    header("Location: sliderManagement.php?success=Slider%20başarıyla%20güncellendi!");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
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
    
    <div class="form-container">
        <!-- Slider Ekleme Formu -->
        <div class="form-section">
            <form action="sliderManagement.php" method="POST" class="form" enctype="multipart/form-data"> <!-- enctype ayarlandı -->
                <h2>Yeni Slider Ekle</h2>
                <input type="text" name="title" placeholder="Başlık" maxlength="30">
                <input type="text" name="summary" placeholder="Özet" maxlength="30">
                
                <!-- Resim Yükleme Alanı -->
                <label for="slider_image">Slider Resmi Yükle:</label>
                <input type="file" id="slider_image" name="slider_image" accept="image/*" required>
                
                <input type="text" name="link" placeholder="Bağlantı" >
                <button type="submit" name="addSlider">Slider Ekle</button>
            </form>
        </div>

    <!-- Düzenleme Formu -->
    <?php if (isset($_GET['edit'])): 
        $id = $_GET['edit'];
        $slider = $conn->query("SELECT * FROM main_page_sliders WHERE id = $id")->fetch_assoc();
    ?>
    <div class="form-section">
        <form action="sliderManagement.php" method="POST" class="form" enctype="multipart/form-data"> <!-- enctype ayarlandı -->
            <h2>Slider Düzenle</h2>
            <input type="hidden" name="id" value="<?php echo $slider['id']; ?>">
            <input type="hidden" name="current_image_path" value="<?php echo $slider['image_path']; ?>"> <!-- Mevcut resim yolu -->
            <input type="text" name="title" value="<?php echo $slider['title']; ?>" maxlength="30">
            <input type="text" name="summary" value="<?php echo $slider['summary']; ?>" maxlength="30">
            
            <!-- Resim Yükleme Alanı -->
            <label for="slider_image">Yeni Slider Resmi Yükle (İsteğe Bağlı):</label>
            <input type="file" id="slider_image" name="slider_image" accept="image/*">
            
            <input type="text" name="link" value="<?php echo $slider['link']; ?>" >
            <button type="submit" name="updateSlider">Slider Güncelle</button>
        </form>
    </div>
    <?php endif; ?>

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
</div>
</body>
</html>