<?php
// Veritabanı bağlantısını yap
include 'dB/database.php';
include 'navbar.php';
include 'bootstrap.php';

// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Haber ID'sini al
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ID'ye göre haber detaylarını al
$newsQuery = "SELECT id, name, summary, text, image_path1, image_path2, image_path3, created_at FROM news WHERE id = ?";
$stmt = $conn->prepare($newsQuery);

if (!$stmt) {
    echo "Sorgu hazırlama hatası: " . $conn->error;
    exit;
}

$stmt->bind_param("i", $newsId);
$stmt->execute();

// Sonuçları elde et
$stmt->store_result(); // Sonuçları sakla
$stmt->bind_result($id, $name, $summary, $text, $image_path1, $image_path2, $image_path3, $created_at);
$stmt->fetch(); // Verileri çek

// Eğer haber bulunamazsa
if (!$id) {
    echo "<p>Haber bulunamadı.</p>";
    exit;
}

function makeClickableLinks($text) {
    // URL kalıplarını bul ve <a> etiketiyle sar
    $text = preg_replace(
        [
            '~(http(s)?://[^\s<]+)~i',                        // http veya https ile başlayan linkler
            '~\b(www\.[^\s<]+?\.(com|net|org|edu|gov|mil|biz|info|io|me|co|com\.tr|net\.tr|org\.tr)\b)~i'  // www ile başlayıp, geçerli TLD ile biten linkler
        ],
        [
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',  // http/https linkleri
            '<a href="http://$1" target="_blank" rel="noopener noreferrer">$1</a>'  // www ile başlayan linkler (http ekleniyor)
        ],
        $text
    );

    // Satır sonlarını koru
    return nl2br($text);
}

// Linkleri tıklanabilir hale getir
$formattedText = makeClickableLinks(htmlspecialchars($text));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haber Detayları</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/news_details.css"> <!-- CSS dosyasını buraya ekleyin -->
</head>
<body>

<div class="container mt-5">
    <div class="slider-container">
        <div id="newsSlider" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner">
                <?php
                // Slider için resimleri al
                $images = [
                    $image_path1,
                    $image_path2,
                    $image_path3,
                ];

                foreach ($images as $index => $image) {
                    if (!empty($image)) {
                        $activeClass = $index === 0 ? 'active' : '';
                        echo "<div class='carousel-item $activeClass'>
                                <img src='" . htmlspecialchars($image) . "' class='d-block w-100 slider-image' alt='Haber Resmi' onclick='openModal(\"" . htmlspecialchars($image) . "\")'>
                              </div>";
                    }
                }
                ?>
            </div>
            <a class="carousel-control-prev" href="#newsSlider" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Önceki</span>
            </a>
            <a class="carousel-control-next" href="#newsSlider" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Sonraki</span>
            </a>
        </div>


                    <!-- Modal (Resmi %50 büyüterek açan) -->
<div id="imageModal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="background: rgba(0,0,0,0.8); border: none;">
      <div class="modal-body p-0 d-flex justify-content-center align-items-center">
        <!-- Resmi ve Kapatma Butonunu kapsayan container -->
        <div class="image-container" style="position: relative; display: inline-block;">
          <img id="modalImage" src="" alt="Büyütülmüş Resim" style="display: block;">
          
        </div>
      </div>
    </div>
  </div>
</div>


            <!-- JavaScript ile Modalı Açma -->
            <script>

                
            function openModal(imageSrc) {
                if (window.innerWidth <= 768) {
                    return; // Mobilde pop-up'ı engelle
                }
                const modalImage = document.getElementById("modalImage");
                modalImage.src = imageSrc;

                modalImage.onload = function() {
                // Orijinal boyutları al (piksel cinsinden)
                const originalWidth = modalImage.naturalWidth;
                const originalHeight = modalImage.naturalHeight;

                // %50 daha büyük (1.5 kat)
                let newWidth = originalWidth * 1.5;
                let newHeight = originalHeight * 1.5;

                // Ekranın %90’ına sığacak şekilde sınır belirleyelim
                const maxW = window.innerWidth * 0.9;
                const maxH = window.innerHeight * 0.9;

                // En-boy oranını koruyarak ekrana sığdıralım
                const widthRatio = maxW / newWidth;
                const heightRatio = maxH / newHeight;
                const scaleFactor = Math.min(widthRatio, heightRatio, 1);

                // scaleFactor < 1 ise, resim ekrandan taşacak kadar büyük
                // olduğundan otomatik küçültülür
                newWidth *= scaleFactor;
                newHeight *= scaleFactor;

                // Yeni boyutları uygula
                modalImage.style.width = newWidth + "px";
                modalImage.style.height = newHeight + "px";

                // Modal'ı göster
                $("#imageModal").modal("show");
                };
            }
            </script>




        </div>

    <!-- Haber Başlığı ve İçeriği -->
    <h2 class="mt-4"><?php echo htmlspecialchars($name); ?></h2>
    <p class="mt-2 news-text"><?php echo $formattedText; ?></p>
</div>

<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
