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
    <link rel="stylesheet" href="css/ulumega.css"> <!-- CSS dosyasını ekledik -->
    <style>
        /* Modal stili */
        .modal {
            display: none; /* Varsayılan olarak gizli */
            position: fixed; /* Sabit pozisyon */
            z-index: 10; /* Diğer içeriklerin üstünde */
            left: 50%; /* Ortalamak için soldan %50 */
            top: 50%; /* Ortalamak için üstten %50 */
            transform: translate(-50%, -50%) scale(0); /* Ortalamak için kaydırma ve başlangıçta küçültme */
            width: 70%; /* Tam genişlik */
            height: 70%; /* Tam yükseklik */
            overflow: auto; /* Taşma durumunda kaydırma */
            border-radius: 8px; /* Köşeleri yuvarlama */
            transition: transform 0.3s ease, opacity 0.3s ease; /* Efekt geçişi */
            opacity: 0; /* Başlangıçta görünmez */
        }

        .modal.show {
            opacity: 1; /* Görünür hale gel */
            transform: translate(-50%, -50%) scale(1); /* Modal açıldığında normal boyuta getir */
        }

        .modal-content {
            margin: auto;
            display: block; /* Görünür yap */
            max-width: 100%; /* Maksimum genişlik */
            max-height: 100%; /* Maksimum yükseklik */
        }

        .close {
            position: absolute;
            top: 10px;
            right: 25px;
            color: white; /* Beyaz çarpı rengi */
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            z-index: 11; /* Çarpı butonu modalın üstünde görünmesi için */
            background: rgba(0, 0, 0, 0.5); /* Çarpı butonunun arka planı */
            border-radius: 50%; /* Çarpı butonunun köşelerini yuvarla */
            padding: 5px 10px; /* Çarpı butonuna boşluk ekle */
        }

        .close:hover,
        .close:focus {
            color: #bbb; /* Hover durumu için renk */
            text-decoration: none;
            cursor: pointer;
        }

        /* Galeri resmi stili */
        .image-gallery {
            display: flex; /* Resimleri yan yana dizmek için flex kullan */
            justify-content: center; /* Yatayda ortala */
            align-items: flex-start; /* Dikeyde üstte hizala */
            margin: 20px 0; /* Yukarıdan ve alttan 20px boşluk bırak */
            z-index: 3; /* Diğer içeriklerin üstünde olmasını sağla */
            position: relative; /* Diğer içeriklerle karışmaması için */
            margin-top: auto; /* Galeriyi sayfanın en altına itmek için */
            flex-wrap: wrap; /* Taşmayı engellemek için sar */
        }

        .image-gallery img {
            display: inline-block; /* Resimleri inline-block olarak ayarla */
            width: 150px; /* Resimlerin genişliğini ayarlayın */
            height: auto; /* Yükseklik oranını koru */
            margin: 0 10px; /* Resimler arasında boşluk bırak */
            transition: transform 0.3s ease; /* Yumuşak geçiş efekti */
            cursor: pointer; /* İmleci tıklanabilir işaretine dönüştür */
            z-index: 5; /* Arka plandan önde görünmesi için */
        }

        /* Resim tıklandığında büyütme efekti için stil */
        .image-gallery img:hover {
            transform: scale(1.05); /* Resmi biraz büyüt */
        }

        /* Mobil stil ayarlamaları */
        @media (max-width: 768px) {
            .modal {
                width: 100%; /* Mobilde genişliği artır */
                height: 100%; /* Mobilde yükseklik artır */
                background: none;
                margin-top: 80%;
            }

            .modal-content {
                max-width: 90%; /* Resmin mobilde maksimum genişliğini ayarlayın */
                max-height: 90%; /* Resmin mobilde maksimum yüksekliğini ayarlayın */
            }

            .close {
                display: none;
            }

            /* Galeri için mobil uyum */
            .image-gallery img {
                width: 100px;
                margin: 10px; /* Resimler arasında boşluk bırak */
            }
        }
    </style>
</head>
<body>

    <!-- Video Arka Planı -->
    <div class="video-container">
        <video autoplay muted loop id="background-video">
            <source src="images/Testvideo.mp4" type="video/mp4">
            Your browser does not support HTML5 video.
        </video>

        <!-- Ortadaki Başlık ve Açıklama -->
        <div class="overlay-content">
            <h1><?php echo $pageData['header']; ?></h1>
            <p><?php echo $pageData['text']; ?></p>
            <!-- Takip Et Butonu -->
            <a href="organizations" class="btn">Yarışları Takip Et</a>
        </div>
    </div>

    <!-- Resim Galerisi, yalnızca resimler varsa göster -->
    <?php if ($hasImages): ?>
    <div class="image-gallery">
        <?php foreach ($imagePaths as $imagePath): ?>
            <img src="<?php echo $imagePath; ?>" alt="Resim" onclick="openModal('<?php echo $imagePath; ?>');" class="image-popup">
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Modal Yapısı -->
    <div id="myModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage" alt="Modal Resmi">
    </div>

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
