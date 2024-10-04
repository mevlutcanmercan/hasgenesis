<?php
include 'sidebar.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <link rel="stylesheet" href="admincss/adminmainpage.css"> <!-- CSS dosyasına bağlantı -->
    <title>Admin Paneli</title>
</head>
<body>



    <div class="main-content">
            <div class="logoo">
                <img src="../images/logo-has.png" alt="Logo" class="logo-image">
            </div>
            <div class="welcome-message">
                <h1>Has Genesis Web Sayfası Paneline Hoşgeldiniz</h1>
            </div>
    </div>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Logo ve mesajın başlangıç pozisyonlarını belirleyin
            const logo = document.querySelector(".logo-image");
            const welcomeMessage = document.querySelector(".welcome-message h1");
            
            // Logo animasyonu
            logo.style.opacity = 0;
            logo.style.transform = 'translateY(-50px)';
            logo.style.transition = 'opacity 1s ease, transform 1s ease';

            // Hoşgeldiniz mesajı animasyonu
            welcomeMessage.style.opacity = 0;
            welcomeMessage.style.transform = 'translateY(20px)';
            welcomeMessage.style.transition = 'opacity 1s ease, transform 1s ease';

            // Geçiş efektleri için zamanlayıcılar
            setTimeout(() => {
                logo.style.opacity = 1;
                logo.style.transform = 'translateY(0)';
            }, 200);

            setTimeout(() => {
                welcomeMessage.style.opacity = 1;
                welcomeMessage.style.transform = 'translateY(0)';
            }, 500);
        });
    </script>

</body>
</html>