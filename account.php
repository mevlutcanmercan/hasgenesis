<?php
include 'dB/database.php';
include 'bootstrap.php';
include 'navbar.php'; // Navbar burada include ediliyor

session_start(); // Kullanıcı oturum kontrolü
$user_id = $_SESSION['id_users']; // Oturumdaki kullanıcı ID'sini al

$stmt = $conn->prepare("SELECT mail_users, name_users, surname_users, telefon, birthday_users FROM users WHERE id_users = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email, $name, $surname, $telefon, $birthday);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hesap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="/hasgenesis/images/logo-empty.png" alt="Logo">
        </div>
        <nav class="mt-4">
            <a href="#profile" class="nav-link active" data-bs-toggle="tab"><i class='bx bxs-user'></i> <span>Profil</span></a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="tab-content">
            <!-- Profile Tab -->
            <div class="tab-pane fade show active" id="profile">
                <h2>Profil Bilgileri</h2>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Ad:</strong> <?php echo htmlspecialchars($name); ?></p>
                        <p><strong>Soyad:</strong> <?php echo htmlspecialchars($surname); ?></p>
                        <p><strong>E-posta:</strong> <?php echo htmlspecialchars($email); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Telefon:</strong> <?php echo htmlspecialchars($telefon); ?></p>
                        <p><strong>Doğum Tarihi:</strong> <?php echo htmlspecialchars($birthday); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar'da aktif linkin kontrol edilmesi
        const links = document.querySelectorAll('.sidebar .nav-link');
        links.forEach(link => {
            link.addEventListener('click', function() {
                links.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
