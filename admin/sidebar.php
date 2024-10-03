<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admincss/sidebar.css"> <!-- CSS dosyası -->
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"> <!-- Boxicons -->
    <title>Admin Paneli</title>
</head>
<body>

    <div class="toggle-icon" id="toggle-icon" style="left: 250px;">
    <i class='bx bx-chevrons-left' ></i> <!-- Sol ok ikonu (açık) -->
    </div>
    
    <div class="sidebar" id="sidebar" style="transform: translateX(0);">
        <div class="logo-container">
            <img src="../images/logo-has.png" alt="Admin Logo" class="logo">
        </div>
        <h2>Admin Paneli</h2>
        <div class="menu">
            <ul>
                <li><a href="AnaSayfa.php" class="active">Ana Sayfa</a></li>
                <li><a href="slideryonetimi.php">Slider Yönetimi</a></li>
                <li><a href="projeyonetimi.php">Proje Yönetimi</a></li>
                <li><a href="MedyaYonetimi.php">Medya Yönetimi</a></li>
                <li><a href="HakkimizdaYonetimi.php">Hakkımızda Yönetimi</a></li>
                <li><a href="IletisimYonetimi.php">İletişim Yönetimi</a></li>
                <li class="cikisyap"><a href="adminlogout.php">Çıkış Yap</a></li>
            </ul>
        </div>
    </div>

    <script>
        const toggleIcon = document.getElementById('toggle-icon');
        const sidebar = document.getElementById('sidebar');
        
        // Sidebar durumunu kontrol et
        let isOpen = true; // Başlangıçta sidebar açık

        // İkon tıklama olayını dinle
        toggleIcon.addEventListener('click', () => {
            if (isOpen) {
                sidebar.style.transform = 'translateX(-100%)'; // Kapatma animasyonu
                toggleIcon.innerHTML = "<i class='bx bx-chevrons-right'></i>"; // Sağ ok ikonu (kapalı)
                toggleIcon.style.left = '10px'; // İkonu sola kaydır
            } else {
                sidebar.style.transform = 'translateX(0)'; // Açma animasyonu
                toggleIcon.innerHTML = "<i class='bx bx-chevrons-left' ></i>"; // Sol ok ikonu (açık)
                toggleIcon.style.left = '250px'; // İkonu sağa kaydır
            }
            isOpen = !isOpen; // Durumu değiştir
        });
    </script>

</body>
</html>
