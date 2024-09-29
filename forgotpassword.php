

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/forgotpassword.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="shortcut icon" href="../images/admin-panel.png" type="image/x-icon">
</head>
<body>

    <div class="wrapper">
        <form action="sendmail.php" method="POST">  
            <div class="back-button">
                <a href="login"><i class='bx bx-arrow-back' style="color: black;"></i></a>
            </div>

            <div class="logo"><img src="./images/logo-has.png" alt=""></div>
            <hr class="cizgi">
            <h1>Şifremi Unuttum</h1>
             
            <div class="input-box">
                <input type="email" name="eposta" placeholder="E-posta Giriniz" required>
                <i class='bx bx-envelope' style="color:black"></i>
            </div>

            <button type="submit" class="btn">Şifremi Gönder</button>
        </form>
    </div>

   
</body>
</html>
