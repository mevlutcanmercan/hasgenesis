<?php
include 'dB/database.php';
include 'bootstrap.php';
$currentPage = basename($_SERVER['SCRIPT_NAME']);

session_start();
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

    // Profil ikonu ile açılır menü
    $profileDropdown = "
        <li class='nav-item dropdown'>
            <a class='nav-link dropdown-toggle' href='#' id='navbarDropdown' role='button' data-bs-toggle='dropdown' aria-expanded='false'>
                <i class='bi bi-person-circle' style='font-size: 24px;'></i> <!-- Profil ikonu -->
            </a>
            <ul class='dropdown-menu dropdown-menu-end' aria-labelledby='navbarDropdown'>
                <li><a class='dropdown-item' href='#'>Profilim</a></li>
                <li><a class='dropdown-item' href='#'>Ayarlar</a></li>
                <li><hr class='dropdown-divider'></li>
                <li><a class='dropdown-item' href='/hasgenesis/logout.php'>Çıkış Yap</a></li>
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
    <meta name="viewport" content="width=device-width">
    <link rel="stylesheet" href="css/navbar.css">
    <title>Document</title>
</head>
<body>
    
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light d-flex justify-content-between">
    <div class="container-fluid">
        <a class="navbar-brand header-text" href="#">HAS GENESIS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
        <div class="navbar-nav mx-auto justify-content-center" id="nav-link-main">
                <a class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" aria-current="page" href="index.php">Ana Sayfa</a>
                <a class="nav-link <?php echo ($currentPage == 'projeler.php') ? 'active' : ''; ?>" href="projeler.php">HAS CREW</a>
                <a class="nav-link <?php echo ($currentPage == 'Medya.php') ? 'active' : ''; ?>" href="Medya.php">PROJELER</a>
                <a class="nav-link <?php echo ($currentPage == 'hakkimizda.php') ? 'active' : ''; ?>" href="hakkimizda.php">HABER BÜLTENİ</a>
                <a class="nav-link <?php echo ($currentPage == 'iletisim.php') ? 'active' : ''; ?>" href="iletisim.php">ORGANİZASYONLAR</a>
                <a class="nav-link <?php echo ($currentPage == 'bizkimiz.php') ? 'active' : ''; ?>" href="bizkimiz.php">BİZ KİMİZ?</a>
            </div>
            <div class="navbar-nav d-flex align-items-center">
                <?php echo $profileDropdown; ?> 
                <?php echo $loginLink; ?>
            </div>
        </div>
    </div>
</nav>

</body>
</html>