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
        $images = array_filter([
            $row['detailsImagePath'], 
            $row['detailsImagePath2'], 
            $row['detailsImagePath3'], 
            $row['detailsImagePath4'], 
            $row['detailsImagePath5']
        ]); // Boş olmayan resimleri al
        $row['images'] = $images; // Resim dizisini ekle
        $crewMembers[] = $row;
    }
}
?>
<!-- düzetme -->
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/hascrew.css">
    <link rel="stylesheet" href="css/footer.css"> <!-- Stil dosyanız burada -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    <title>Has Crew</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>


<!-- Swiper Slider -->
<div class="swiper-container">
    <div class="swiper-wrapper">
        <?php foreach ($crewMembers as $member): ?>
        <div class="swiper-slide" 
            data-id="<?php echo $member['id']; ?>" 
            data-details="<?php echo htmlspecialchars($member['memberDetail']); ?>" 
            data-images="<?php echo htmlspecialchars(json_encode($member['images'])); ?>"
            data-instagram="<?php echo htmlspecialchars($member['instagram']); ?>"
            data-twitter="<?php echo htmlspecialchars($member['twitter']); ?>"
            data-youtube="<?php echo htmlspecialchars($member['youtube']); ?>"
        >
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
    <div class="name-and-social">
        <h3 id="member-name"></h3>
        <div class="social-icons" id="social-icons">
            <!-- Sosyal medya ikonları dinamik olarak eklenecek -->
        </div>
    </div>
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
        const instagram = activeSlide.dataset.instagram;
        const twitter = activeSlide.dataset.twitter;
        const youtube = activeSlide.dataset.youtube;

        document.getElementById('member-name').innerText = memberName;
        document.getElementById('member-detail').innerText = memberDetail;

        const socialIconsContainer = document.getElementById('social-icons');
        socialIconsContainer.innerHTML = ''; 

        if (instagram) {
            const a = document.createElement('a');
            a.href = `https://www.instagram.com/${instagram}`;
            a.target = '_blank';
            a.innerHTML = '<i class="fab fa-instagram"></i>';
            socialIconsContainer.appendChild(a);
        }

        if (twitter) {
            const a = document.createElement('a');
            a.href = `https://twitter.com/${twitter}`;
            a.target = '_blank';
            a.innerHTML = '<i class="fab fa-twitter"></i>';
            socialIconsContainer.appendChild(a);
        }

        if (youtube) {
            const a = document.createElement('a');
            a.href = `https://www.youtube.com/${youtube}`;
            a.target = '_blank';
            a.innerHTML = '<i class="fab fa-youtube"></i>';
            socialIconsContainer.appendChild(a);
        }

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
        // Sayfanın kaymasını engellemek için scrollIntoView kaldırıldı
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
