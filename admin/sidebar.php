<?php
// Database bağlantısı ve oturum başlatma
include '../dB/database.php';

session_start();

$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admincss/sidebar.css"> <!-- CSS dosyası -->
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"> <!-- Boxicons -->
    <link rel="shortcut icon" href="../images/admin-icon.png" type="image/x-icon">
    
</head>
<body>

    <!-- Toggle İkonu -->
    <div class="toggle-icon" id="toggle-icon" style="left: 10px;">
        <i class='bx bx-chevrons-right'></i> <!-- Sağ ok ikonu (kapalı) -->
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar" style="transform: translateX(-100%);">
        <div class="logo-container">
            <img src="../images/logo-has.png" alt="Admin Logo" class="logo">
        </div>
        <h2>Admin Paneli</h2>
        <div class="menu">
            <ul>
                <li><a href="adminmainpage" class="<?php echo ($currentPage == 'adminmainpage.php') ? 'active' : ''; ?>">Ana Sayfa</a></li>
                <li><a href="news-management" class="<?php echo ($currentPage == 'news-management.php') ? 'active' : ''; ?>">Haber Yönetimi</a></li>
                <li><a href="projeyonetimi.php" class="<?php echo ($currentPage == 'projeyonetimi.php') ? 'active' : ''; ?>">Proje Yönetimi</a></li>
                <li><a href="MedyaYonetimi.php" class="<?php echo ($currentPage == 'MedyaYonetimi.php') ? 'active' : ''; ?>">Medya Yönetimi</a></li>
                <li><a href="HakkimizdaYonetimi.php" class="<?php echo ($currentPage == 'HakkimizdaYonetimi.php') ? 'active' : ''; ?>">Hakkımızda Yönetimi</a></li>
                <li><a href="communication-management" class="<?php echo ($currentPage == 'communication-management.php') ? 'active' : ''; ?>">İletişim Yönetimi</a></li>
                <li class="cikisyap"><a href="adminlogout.php">Çıkış Yap</a></li>
            </ul>
        </div>
    </div>

    <script>
        const toggleIcon = document.getElementById('toggle-icon');
        const sidebar = document.getElementById('sidebar');
        
        // Sidebar başlangıçta kapalı
        let isOpen = false; // Başlangıçta sidebar kapalı

        // İkon tıklama olayını dinle
        toggleIcon.addEventListener('click', () => {
            if (isOpen) {
                sidebar.style.transform = 'translateX(-100%)'; // Kapatma animasyonu
                toggleIcon.innerHTML = "<i class='bx bx-chevrons-right'></i>"; // Sağ ok ikonu (kapalı)
                toggleIcon.style.left = '10px'; // İkonu sola kaydır
            } else {
                sidebar.style.transform = 'translateX(0)'; // Açma animasyonu
                toggleIcon.innerHTML = "<i class='bx bx-chevrons-left'></i>"; // Sol ok ikonu (açık)
                toggleIcon.style.left = '15%'; // İkonu sidebar'a yakın konumla
            }
            isOpen = !isOpen; // Durumu değiştir
        });
    </script>

</body>
</html>
