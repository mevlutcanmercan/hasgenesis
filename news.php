<?php
include 'dB/database.php';
include 'navbar.php';
include 'bootstrap.php';

// Türkçe dilini ayarla
setlocale(LC_TIME, 'tr_TR.UTF-8');

// Sayfa numarasını al (Eğer tanımlı değilse varsayılan olarak 1)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Sayfa başına kaç haber gösterileceğini belirle
$newsPerPage = 6; // Her sayfada 6 haber gösterilecek

// Toplam haber sayısını bul
$totalNewsQuery = "SELECT COUNT(*) as total FROM news";
$totalNewsResult = $conn->query($totalNewsQuery);
$totalNews = $totalNewsResult->fetch_assoc()['total'];

// Toplam sayfa sayısını hesapla
$totalPages = ceil($totalNews / $newsPerPage);

// Eğer geçersiz bir sayfa numarası gelirse varsayılan olarak 1. sayfaya git
if ($page < 1) $page = 1;
if ($page > $totalPages) $page = $pages; // Bu satırda bir hata vardı, $pages yerine $totalPages kullanılmalı.

// Hangi haberi alacağını belirle (OFFSET ve LIMIT kullanarak)
$offset = ($page - 1) * $newsPerPage;
$newsQuery = "SELECT id, name, summary, image_path1, created_at FROM news ORDER BY created_at DESC LIMIT $newsPerPage OFFSET $offset";
$newsResult = $conn->query($newsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haber Bülteni</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/news.css"> <!-- CSS dosyasını ekleyin -->
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center mb-4 fade-in" id="projects-title">Haber Bülteni</h2>
    <div class="row" id="news-cards">
        <?php if ($newsResult->num_rows == 0): ?>
            <p class="text-center">Henüz Haber Bulunmuyor.</p>
        <?php else: ?>
            <?php while($news = $newsResult->fetch_assoc()): ?>
                <div class="col-md-4 mb-4 fade-in-card">
                    <div class="project-card">
                        <img src="<?php echo htmlspecialchars($news['image_path1']); ?>" alt="Proje Resmi" class="project-image img-fluid">
                        <div class="project-card-body">
                            <h3 class="project-title"><?php echo htmlspecialchars(substr($news['name'], 0, 50)); ?></h3>
                            <p class="project-date">
                                <?php echo strftime("%d.%m.%Y", strtotime($news['created_at'])); ?>
                            </p>
                            <p class="project-summary">
                                <?php echo htmlspecialchars(substr($news['summary'], 0, 175)) . (strlen($news['summary']) > 175 ? '...' : ''); ?>
                            </p>
                        </div>
                        <div class="project-footer">
                            <a href="news_details.php?id=<?php echo $news['id']; ?>" class="btn btn-primary">Detaya Git</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <div class="pagination-container">
        <!-- Sayfalama Bağlantıları -->
        <?php if ($page > 1): ?>
            <a href="news.php?page=<?php echo $page - 1; ?>" class="btn btn-outline-primary pagination-link">Önceki</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="news.php?page=<?php echo $i; ?>" class="btn btn-outline-primary pagination-link <?php if ($i == $page) echo 'active'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="news.php?page=<?php echo $page + 1; ?>" class="btn btn-outline-primary pagination-link">Sonraki</a>
        <?php endif; ?>
    </div>
</div>

<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const cards = document.querySelectorAll('.fade-in-card');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    // Gecikme ile animasyon
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 150);  // Her kart için 100ms gecikme
                }
            });
        }, { threshold: 0.1 }); // Kartın %10'u göründüğünde tetiklenir

        cards.forEach(card => {
            observer.observe(card);
        });
    });
</script>

</body>
</html>
