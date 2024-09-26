<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Kayıt Ol</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="/hasgenesis/css/login.css"> -->
    <link rel="stylesheet" href="/hasgenesis/css/Register.css">
</head>
<body>
    <div class="wrapper">
        <form action="register.php" method="post">
        <div class="back-button">
                <a href="login.php"><i class='bx bx-arrow-back' style="color: black;"></i></a>
            </div>
            <div class="logo"><img src="./images/logo-empty.png" alt=""></div>
            
            <hr class="cizgi">
            <h1>Kayıt Ol</h1>

            <div class="input-box">
                <input type="text" name="" placeholder="Ad Soyad" required>
                <i class='bx bxs-user' style="color:black"></i>
            </div>

            <div class="input-box">
                <input type="text" name="" placeholder="Telefon No" required>
                <i class='bx bxs-user' style="color:black"></i>
            </div>

            <!-- Date Picker for Doğum Tarihi -->
            <div class="input-box">
                <input type="date" name="dob" placeholder="Doğum Tarihi" required>
                <i class='bx bxs-calendar' style="color:black"></i>
            </div>

            <div class="input-box">
            <select name="bisiklet_marka" required>
                <option value="" disabled selected hidden>Bisiklet Markası Seç</option>
                <option value="Bianchi">Bianchi</option>
                <option value="Salcano">Salcano</option>
                <option value="Carraro">Carraro</option>
            </select>
            <i class='bx bxs-bike' style="color:black"></i>
            </div>

            <div class="input-box">
                <input type="email" name="mail_users" placeholder="Eposta Adresi" required>
                <i class='bx bxs-user' style="color:black"></i>
            </div>

            <div class="input-box">
                <input type="password" name="password_users" placeholder="Şifre" id="password"  required>
                <i class='bx bxs-lock-alt' style="color:black"></i>
            </div>

            
            
            <button type="submit" class="btn">Kayıt Ol</button>
        </form>
        
    </div>
</body>
</html>
