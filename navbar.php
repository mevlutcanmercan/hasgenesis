<?php
$welcomeMessage = "";
$logoutLink = "";
$loginLink = "<a href='/hasgenesis/login.php' class='btn-login'>Giriş Yap</a>";
 
$profile = "";
if (isset($_SESSION['id_users'])) {
    $userID = $_SESSION['id_users'];

    $userQuery = "SELECT * FROM users WHERE id_users= $userID";
    $userResult = $conn->query($userQuery);

    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $name = $user['name_users']; 
        $welcomeMessage = "<h1 id='hosgeldin' class='welcome-message'>Hoşgeldiniz, " . $name . "</h1>";
    }

    $logoutLink = "<a class='nav-link' href='/hasgenesis/logout.php'>Çıkış Yap</a>";
    $loginLink = ""; 
}

?>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light d-flex justify-content-between">
    <div class="container-fluid">
        <a class="navbar-brand header-text" href="#">HAS GENESIS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav mx-auto justify-content-center" id="nav-link-main">
                <a class="nav-link active" aria-current="page" href="#">Ana Sayfa</a>
                <a class="nav-link" href="projeler.php">HAS CREW</a>
                <a class="nav-link" href="Medya.php">PROJELER</a>
                <a class="nav-link" href="hakkimizda.php">HABER BÜLTENİ</a>
                <a class="nav-link" href="iletisim.php">ORGANİZASYONLAR</a>
                <a class="nav-link" href="iletisim.php">BİZ KİMİZ ?</a>
            </div>
            <div class="navbar-nav"> 
            <?php echo $logoutLink ; ?>                       
            <?php echo $welcomeMessage ; ?>
            <?php echo $loginLink ;  ?>
            </div>
        </div>
    </div>
</nav>




     