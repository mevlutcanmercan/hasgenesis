<?php
include 'dB/database.php';
include 'navbar.php';
include 'bootstrap.php';

// Crew üyelerini veritabanından çekme
$query = "SELECT * FROM has_crew";
$result = $conn->query($query);

$crewMembers = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $images = array_filter([$row['detailsImagePath'], $row['detailsImagePath2'], $row['detailsImagePath3'], $row['detailsImagePath4'], $row['detailsImagePath5']]); // Boş olmayan resimleri al
        $row['images'] = $images; // Resim dizisini ekle
        $crewMembers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/hascrew.css">
    <link rel="stylesheet" href="css/footer.css"> <!-- Stil dosyanız burada -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    <title>Has Crew</title>
   
</head>
<body>
<style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f0f0f0;
        margin: 0;
        overflow-x: hidden;
        padding-top: 200px;
    }
    .swiper-container {
        width: 80%;
        height: 400px; /* Slider yüksekliği */
        margin-bottom: 20px; /* Slider ve detaylar arasında boşluk */
        margin-top: 10%;
    }

    .swiper-slide {
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0.5; /* Küçük slide'lar için başlangıç opaklığı */
        transform: scale(0.8); /* Küçük slide'lar için ölçek */
        transition: all 0.3s ease; /* Geçiş animasyonu */
        border-radius: 15px; /* Kenar yuvarlama */
        overflow: hidden; /* Taşmayı önlemek için */
        position: relative; /* Hover resmi için gerekli */
    }

    .swiper-slide-active {
        opacity: 1; /* Aktif slide için tam opaklık */
        transform: scale(1.2) !important; /* Aktif slide için daha büyük ölçek */
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); /* Gölgelendirme efekti */
        z-index: 1; /* Aktif slide'ın üstündeki diğer slide'ların gözükmesi için */
    }

    .swiper-slide img {
        width: 100%;
        border-radius: 15px; /* Resim kenar yuvarlama */
        transition: transform 0.3s ease; /* Resim geçişi */
    }

    .swiper-slide:hover img {
        transform: scale(1.05); /* Hover efekti */
    }

    .info {
        position: absolute; /* Bilgi kısmını resmin üzerine yerleştirmek için */
        bottom: 10px; /* Resmin alt kısmında */
        left: 10px; /* Soldan bir miktar içeride */
        color: white; /* Yazı rengi beyaz */
        background-color: rgba(0, 0, 0, 0.7); /* Koyu arka plan */
        padding: 10px;
        border-radius: 5px; /* Kenar yuvarlama */
    }

    .details {
        text-align: left; /* Yazı hizalaması sola */
        margin: 20px;
        width: 80%; /* Detay kısmının genişliği */
        background: white; /* Arka plan rengi */
        border-radius: 10px; /* Kenar yuvarlama */
        padding: 15px; /* İç boşluk */
        display: none; /* Başlangıçta gizli */
        word-wrap: break-word; /* Uzun kelimeleri aşağıya sarmala */
        overflow-wrap: break-word; /* Taşan yazıları sarmala */
    }

    .details img {
        width: 200px; /* Resim genişliği */
        height: auto; /* Yüksekliği otomatik ayarlama */
        margin: 10px; /* Resimler arası boşluk */
        border-radius: 10px; /* Resim kenar yuvarlama */
        transition: transform 0.3s ease; /* Hover sırasında büyütme efekti */
        cursor: pointer; /* Tıklanabilir olduğunu göster */
    }

    .image-preview-container {
        display: inline-block;
        position: relative;
        margin: 0 10px; /* Resimler arası yatay boşluk */
    }

    .swiper-button-next,
    .swiper-button-prev {
        color: #333; /* Buton rengi */
    }

    .swiper-button-next:after,
    .swiper-button-prev:after {
        font-size: 30px; /* Buton yazı boyutu */
    }

    .swiper-pagination-bullet {
        background: #333; /* Sayfa numarası rengi */
        opacity: 0.5; /* Sayfa numarası başlangıç opaklığı */
    }

    .swiper-pagination-bullet-active {
        opacity: 1; /* Aktif sayfa numarası opaklığı */
    }

    /* Modal stili */
    #image-modal {
        display: none; /* Başlangıçta gizli */
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8); /* Koyu arka plan */
        justify-content: center;
        align-items: center;
    }

    #image-modal img {
        max-width: 90%;
        max-height: 90%;
    }

    #image-modal .close {
        position: absolute;
        top: 20px;
        right: 40px;
        color: white;
        font-size: 40px;
        cursor: pointer;
    }
</style>

<!-- Swiper Slider -->
<div class="swiper-container">
    <div class="swiper-wrapper">
        <?php foreach ($crewMembers as $member): ?>
        <div class="swiper-slide" data-id="<?php echo $member['id']; ?>" data-details="<?php echo htmlspecialchars($member['memberDetail']); ?>" data-images="<?php echo htmlspecialchars(json_encode($member['images'])); ?>">
            <img src="<?php echo $member['sliderImagePath']; ?>" alt="<?php echo htmlspecialchars($member['memberName']); ?>">
            <div class="info">
                <h2><?php echo htmlspecialchars($member['memberName']); ?></h2>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Navigation buttons -->
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
</div>

<!-- Detay Kısmı: Hover Resimler için -->
<div class="details" id="details">
    <h3 id="member-name"></h3>
    <p id="member-detail"></p>

    <div id="member-images">
        <!-- Detay resimleri burada dinamik olarak oluşturulacak -->
    </div>
</div>

<!-- Modal -->
<div id="image-modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <img id="modal-image" src="" alt="">
</div>

<!-- Swiper JS -->
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
    const swiper = new Swiper('.swiper-container', {
        loop: true,
        slidesPerView: 3,
        spaceBetween: 30,
        centeredSlides: true,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        on: {
            init: function () {
                const initialSlide = this.slides[this.activeIndex];
                updateDetails(initialSlide);
            },
            slideChangeTransitionStart: function () {
                const activeSlide = this.slides[this.activeIndex];
                updateDetails(activeSlide);
            }
        }
    });

    function updateDetails(activeSlide) {
        const memberName = activeSlide.querySelector('.info h2').innerText;
        const memberDetail = activeSlide.dataset.details;
        const memberImages = JSON.parse(activeSlide.dataset.images);

        document.getElementById('member-name').innerText = memberName;
        document.getElementById('member-detail').innerText = memberDetail;

        const imagesContainer = document.getElementById('member-images');
        imagesContainer.innerHTML = ''; // Önceki resimleri temizle
        memberImages.forEach((image) => {
            if (image) {
                const containerDiv = document.createElement('div');
                containerDiv.classList.add('image-preview-container');

                const imgElement = document.createElement('img');
                imgElement.src = image;
                imgElement.alt = "Detail of " + memberName;
                imgElement.classList.add('detail-image');
                imgElement.onclick = function () {
                    openModal(image);
                };

                containerDiv.appendChild(imgElement);
                imagesContainer.appendChild(containerDiv);
            }
        });

        document.getElementById('details').style.display = 'block';
        document.getElementById('details').scrollIntoView({ behavior: 'smooth' });
    }

    function openModal(imageSrc) {
        const modal = document.getElementById('image-modal');
        const modalImg = document.getElementById('modal-image');
        modalImg.src = imageSrc;
        modal.style.display = 'flex';
    }

    function closeModal() {
        const modal = document.getElementById('image-modal');
        modal.style.display = 'none';
    }
</script>
<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>
</body>
</html>