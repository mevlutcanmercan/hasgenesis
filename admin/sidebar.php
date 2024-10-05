<?php
// Database bağlantısı ve oturum başlatma
include '../dB/database.php';
include '../auth.php';
session_start();
requireAdmin();

$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1100">
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
                <li><a href="hascrewmanagement" class="<?php echo ($currentPage == 'hascrewmanagement.php') ? 'active' : ''; ?>">Has Crew</a></li>
                <li><a href="newsManagement" class="<?php echo ($currentPage == 'newsManagement.php') ? 'active' : ''; ?>">Haber Yönetimi</a></li>
                <li><a href="project-managament" class="<?php echo ($currentPage == 'project-managament.php') ? 'active' : ''; ?>">Proje Yönetimi</a></li>
                <li><a href="sliderManagement" class="<?php echo ($currentPage == 'sliderManagement.php') ? 'active' : ''; ?>">Slider Yönetimi</a></li>
                <li><a href="whous-managament" class="<?php echo ($currentPage == 'whous-managament.php') ? 'active' : ''; ?>">Hakkımızda Yönetimi</a></li>
                <li><a href="communication-management" class="<?php echo ($currentPage == 'communication-management.php') ? 'active' : ''; ?>">İletişim Yönetimi</a></li>
                <li class="cikisyap"><a href="adminlogout.php">Çıkış Yap</a></li>
            </ul>
        </div>
    </div>

    <script>
        const toggleIcon = document.getElementById('toggle-icon');
        const sidebar = document.getElementById('sidebar');
        
        // Sidebar başlangıçta kapalı
        let isOpen = false; // Sidebar'ın açık olup olmadığını izlemek için bir bayrak

        // İkon tıklama olayını dinle
        toggleIcon.addEventListener('click', (event) => {
            event.stopPropagation(); // Tıklama olayını diğer yerlere yayılmasını engelle
            if (isOpen) {
                sidebar.style.transform = 'translateX(-100%)'; // Sidebar'ı kapat
                toggleIcon.innerHTML = "<i class='bx bx-chevrons-right'></i>"; // Sağ ok ikonu
                toggleIcon.style.left = '10px'; // İkonu sola kaydır
            } else {
                sidebar.style.transform = 'translateX(0)'; // Sidebar'ı aç
                toggleIcon.innerHTML = "<i class='bx bx-chevrons-left'></i>"; // Sol ok ikonu
                toggleIcon.style.left = '15%'; // İkonu sidebar'a yakınlaştır
            }
            isOpen = !isOpen; // Sidebar'ın durumunu değiştir
        });

        // Tüm sayfaya tıklama olayını dinle
        document.addEventListener('click', (event) => {
            // Eğer sidebar açık ve tıklanan yer sidebar veya toggle butonu değilse
            if (isOpen && !sidebar.contains(event.target) && !toggleIcon.contains(event.target)) {
                sidebar.style.transform = 'translateX(-100%)'; // Sidebar'ı kapat
                toggleIcon.innerHTML = "<i class='bx bx-chevrons-right'></i>"; // Sağ ok ikonu
                toggleIcon.style.left = '10px'; // Toggle ikonunu sola kaydır
                isOpen = false; // Sidebar kapalı duruma getir
            }
        });
    </script>

</body>
</html>
