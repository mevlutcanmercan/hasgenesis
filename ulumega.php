<?php
include 'navbar.php';
include 'bootstrap.php';
include 'db/database.php';

// Veritabanından içeriği al
$sql = "SELECT * FROM ulumega_page LIMIT 1"; // Tek bir kaydı al
$result = $conn->query($sql);
$pageData = $result->fetch_assoc(); // Veriyi al

// Resim yollarını bir diziye at ve boş olanları filtrele
$imagePaths = [
    $pageData['image_path1'],
    $pageData['image_path2'],
    $pageData['image_path3'],
];

// Boş olmayan resimleri filtrele
$imagePaths = array_filter($imagePaths, function($path) {
    return !empty($path); // Yalnızca boş olmayan yolları tut
});

// Resim sayısını kontrol et
$hasImages = count($imagePaths) > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulumega</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/ulumega.css"> <!-- CSS dosyası -->
</head>
<body>

    <!-- Arkaplan video -->
    <video autoplay muted loop class="background-video">
        <source src="images/testvideo.mp4" type="video/mp4"> <!-- Video dosyasının yolu -->
        Tarayıcınız video etiketini desteklemiyor.
    </video>

    <!-- İçerik Bölümü -->
    <div class="container">
        <div class="content">
            <!-- Dinamik başlık ve açıklama -->
            <h1><?php echo $pageData['header']; ?></h1>
            <p><?php echo nl2br($pageData['text']); ?></p>
            <a href="organizations" class="btn">Yarışları Takip Et</a>

            <?php if ($hasImages): ?>
            <div class="image-gallery">
                <?php foreach ($imagePaths as $imagePath): ?>
                    <img src="<?php echo $imagePath; ?>" alt="Resim" onclick="openModal('<?php echo $imagePath; ?>');" class="image-popup">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Popup Penceresi -->
    <div id="myModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage" alt="Modal Resmi">
    </div>

    <!-- JavaScript Kodu -->
    <script>
        // Modalı açma fonksiyonu
        function openModal(imagePath) {
            var modal = document.getElementById("myModal");
            var modalImage = document.getElementById("modalImage");
            modal.style.display = "block"; // Modali aç
            modal.classList.add('show'); // Efekt için show sınıfını ekle
            modalImage.src = imagePath; // Resmi modalda göster

            // CSS geçişi ile modal açılma efekti
            setTimeout(() => {
                modal.style.opacity = 1; // Opaklığı artır
            }, 10); // Geçişin düzgün görünmesi için kısa bir gecikme
        }

        // Modalı kapatma fonksiyonu
        function closeModal() {
            var modal = document.getElementById("myModal");
            modal.style.opacity = 0; // Opaklığı azalt
            modal.classList.remove('show'); // Efekt için show sınıfını kaldır
            setTimeout(() => {
                modal.style.display = "none"; // Modal kapanmadan önce süre ver
            }, 300); // Opaklık animasyonu süresiyle aynı
        }

        // Modalın dışına tıklanıldığında kapatma
        window.onclick = function(event) {
            var modal = document.getElementById("myModal");
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

</body>
</html>
