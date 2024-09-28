<?php
include 'dB/database.php';
include 'bootstrap.php';
include 'auth.php';
requireLogin(); 

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini veritabanından çek
$stmt = $conn->prepare("SELECT mail_users, name_users, surname_users, telefon, birthday_users FROM users WHERE id = ?");
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Custom CSS -->
    <style>
        body {
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
        }
        .sidebar .logo {
            padding: 20px;
            text-align: center;
            background: #23272b;
        }
        .sidebar .logo img {
            max-width: 100%;
            height: auto;
        }
        .sidebar .nav-link {
            color: #fff;
            display: block;
            padding: 15px 20px;
            text-decoration: none;
        }
        .sidebar .nav-link:hover {
            background: #495057;
            color: #fff;
        }
        .content {
            flex: 1;
            padding: 20px;
            background: #f8f9fa;
        }
        @media (max-width: 768px) {
            .sidebar {
                min-width: 100px;
                max-width: 100px;
            }
            .sidebar .nav-link {
                text-align: center;
                padding: 10px 5px;
            }
            .sidebar .nav-link span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="/hasgenesis/images/logo-empty.png" alt="Logo">
        </div>
        <nav class="mt-4">
            <a href="#profile" class="nav-link active" data-bs-toggle="tab"><i class='bx bxs-user'></i> <span>Profil</span></a>
            <!-- Daha fazla sekme eklemek için buraya ekleyebilirsiniz -->
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
            <!-- Diğer Sekmeler Buraya Eklenecek -->
        </div>
    </div>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Optional: Boxicons JS if needed -->
    <script src='https://unpkg.com/boxicons@2.1.4/dist/boxicons.js'></script>
    <script>
        // Sidebar active link handling
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
