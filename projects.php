<?php
include 'dB/database.php';
include 'navbar.php';
include 'bootstrap.php';

// Pagination settings
$limit = 6; // Number of cards per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page number
$offset = ($page - 1) * $limit; // Calculate offset

// Fetch projects for the slider (change this if you want to fetch all or specific projects)
$sliderProjectsQuery = "SELECT image_path1, image_path2, image_path3, image_path4, image_path5 FROM projects";
$sliderProjectsResult = $conn->query($sliderProjectsQuery);

// Fetch projects for the cards
$query = "SELECT * FROM projects ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Get total record count
$totalQuery = "SELECT COUNT(*) as total FROM projects";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalProjects = $totalRow['total'];
$totalPages = ceil($totalProjects / $limit); // Calculate total pages

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projeler</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/projects.css"> <!-- New CSS file -->
</head>
<style>
    @keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.card,
.project-info,
.carousel-inner {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeIn 0.8s ease forwards; /* Yumuşak geçiş efekti */
    animation-delay: var(--delay); /* Her eleman için farklı gecikme süresi */
}


</style>
<body>

    

<!-- Slider (Visible only on the first page) -->
<?php if ($page === 1): ?>
    <div class="project-info">
    
    <!-- Header with a title -->
    <div class="Header">
    <h2>Projelerimiz</h2>
    </div>
    <div id="projectSlider" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php
            $images = [];

            // Collect image paths from slider projects
            while ($project = $sliderProjectsResult->fetch_assoc()) {
                foreach ($project as $imagePath) {
                    if (!empty($imagePath)) $images[] = $imagePath; // Only add non-empty paths
                }
            }

            // Randomize images for the slider
            shuffle($images);

            // Add images to the slider
            $active = 'active';
            foreach ($images as $image) {
                echo "<div class='carousel-item $active'>
                        <img src='$image' class='d-block w-100' alt='Project Image'>
                      </div>";
                $active = ''; // Remove 'active' class from subsequent items
            }
            ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#projectSlider" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Önceki</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#projectSlider" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Sonraki</span>
        </button>
    </div>

    <!-- Short info about the projects -->
    
        
        <p>
            Ülkemizin dört bir yanı dağlarla çevrilidir. Buna rağmen dağ bisikleti kültürü yok denecek kadar az. Yurt dışında bulunduğumuz süreçte, şahit olduğumuz dağ bisikleti turizmini ülkemizde bisiklet parkları ve dağ bisikleti yolları yaparak bir noktaya getirmektir. Kışın doluluk oranı %100 olan kayak tesislerimiz yazın %5’lere düşmektedir. Amacımız, yaptığımız ve yapacağımız projelerle bu dengesizliğe bir denge getirmektir. Dağ bisikleti, doğayla iç içe adrenalin seviyesi yüksek bir spor dalıdır. İnsanlar, şehir hayatının verdiği monotonluktan dolayı doğayı tercih sebebi olmaya başlamıştır. Biz de bu durumu göz önünde bulundurarak, ülkemizde dağ bisikletine yönelik birçok proje geliştirmiş bulunmaktayız. Bu projelerle, hem dağ bisikleti tutkunlarına yeni alanlar sunmayı hem de dağ bisikleti kültürünü yaymayı hedefliyoruz. Geliştirdiğimiz bu projeleri aşağıdaki kartlardan inceleyebilirsiniz.
        </p>
    </div>
<?php endif; ?>
<hr>
<div class="container mt-4">
    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <img src="<?php echo htmlspecialchars($row['image_path1']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                            <p class="project-date"><small class="text-muted"><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></small></p>
                            <p class="card-text"><?php echo htmlspecialchars($row['summary']); ?></p>
                            
                            <a href="project_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Detaylar</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Hiç proje bulunamadı.</p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination-container">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-outline-primary pagination-link">Önceki</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="btn btn-outline-primary pagination-link <?php if ($i == $page) echo 'active'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-outline-primary pagination-link">Sonraki</a>
        <?php endif; ?>
    </div>
</div>


<script>
  document.addEventListener("DOMContentLoaded", function() {
    const cards = document.querySelectorAll('.card');
    const projectInfo = document.querySelector('.project-info');
    const carouselInner = document.querySelector('.carousel-inner');

    // Project Info ve Slider İçin Animasyon
    if (projectInfo && carouselInner) {
        projectInfo.style.setProperty('--delay', `0.2s`); // Proje bilgisi biraz gecikmeyle görünsün
        carouselInner.style.setProperty('--delay', `0.4s`); // Slider biraz daha geç görünsün
        projectInfo.classList.add('visible');
        carouselInner.classList.add('visible');
    }

    // Kartlar için animasyon
    cards.forEach((card, index) => {
        card.style.setProperty('--delay', `${index * 0.2}s`); // Her kart için gecikme süresi
        card.classList.add('visible'); // Kart görünür hale gelecek
    });
});
</script>




<footer class="footer mt-auto py-2">
    <div class="footer-container text-center">
        <span class="text-muted">HAS GENESIS &copy; 2024. Tüm hakları saklıdır.</span>
    </div>
</footer>

</body>
</html>
