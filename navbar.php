<?php
include 'dB/database.php';


session_start();

$currentPage = basename($_SERVER['SCRIPT_NAME']);
$welcomeMessage = "";
$logoutLink = "";
$loginLink = "<a href='/hasgenesis/login.php' class='btn-login'>Giriş Yap</a>";
$profileDropdown = ""; 

if (isset($_SESSION['id_users'])) {
    $userID = $_SESSION['id_users'];

    $userQuery = "SELECT * FROM users WHERE id_users= $userID";
    $userResult = $conn->query($userQuery);

    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $name = $user['name_users']; 
        $welcomeMessage = "<h1 id='hosgeldin' class='welcome-message'>Hoşgeldiniz, " . $name . "</h1>";
    }

    // Profil ikonu ile açılır menü (Boxicons kullanarak)
    $profileDropdown = "
        <li class='nav-item dropdown'>
            <a class='nav-link dropdown-toggle' href='#' id='navbarDropdown' role='button' data-bs-toggle='dropdown' aria-expanded='false'>
                <i class='bx bxs-user' style='font-size: 24px;'></i> 
            </a>
            <ul class='dropdown-menu dropdown-menu-end' aria-labelledby='navbarDropdown'>
                <li><a class='dropdown-item' href='account'>Profilim</a></li>
                <li><a class='dropdown-item' href='#'>Ayarlar</a></li>
                <li><hr class='dropdown-divider'></li>
                <li><a class='dropdown-item' href='logout'>Çıkış Yap</a></li>
            </ul>
        </li>
    ";

    $loginLink = ""; // Eğer kullanıcı giriş yaptıysa giriş yap butonunu gizle
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/navbar.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="shortcut icon" href="images/logo-has.png" type="image/x-icon">

    <style>
        
        


    </style>
</head>

<body>


    
<!-- Navbar -->
<nav class="navbar navbar-expand-xl navbar-light d-flex justify-content-between">
    <div class="container-fluid">
        <a class="navbar-brand header-text" href="index">HAS GENESIS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav mx-auto justify-content-center" id="nav-link-main">
                <a class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" aria-current="page" href="index">Ana Sayfa</a>
                <a class="nav-link <?php echo ($currentPage == 'hascrew.php') ? 'active' : ''; ?>" href="hascrew">HAS CREW</a>
                <a class="nav-link <?php echo ($currentPage == 'projects.php') ? 'active' : ''; ?>" href="projects">PROJELER</a>
                <a class="nav-link <?php echo ($currentPage == 'news.php') ? 'active' : ''; ?>" href="news">HABER BÜLTENİ</a>
                <a class="nav-link <?php echo ($currentPage == 'organizations.php') ? 'active' : ''; ?>" href="organizations">ORGANİZASYONLAR</a>
                <a class="nav-link <?php echo ($currentPage == 'whous.php') ? 'active' : ''; ?>" href="whous">BİZ KİMİZ ?</a>
                <a class="nav-link <?php echo ($currentPage == 'communication.php') ? 'active' : ''; ?>" href="communication">İLETİŞİM</a>
            </div>
            <div class="navbar-nav d-flex align-items-center">
                <?php echo $profileDropdown; ?> 
                <?php echo $loginLink; ?>
            </div>
        </div>
    </div>
</nav>



<script>
document.addEventListener('DOMContentLoaded', function() {
    const navbarToggle = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('#navbarNavAltMarkup');

    

    // Close navbar when clicking outside
    document.addEventListener('click', function(event) {
        if (!navbarCollapse.contains(event.target) && !navbarToggle.contains(event.target)) {
            navbarCollapse.classList.remove('show');
        }
    });
});
</script>
</body>
</html>



