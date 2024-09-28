<?php
include 'dB/database.php';
include 'navbar.php'; 
include 'bootstrap.php';
session_start(); 
$user_id = $_SESSION['id_users']; 

// Kullanıcı bilgilerini al
$stmt = $conn->prepare("SELECT mail_users, name_users, surname_users, telefon, birthday_users FROM users WHERE id_users = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email, $name, $surname, $telefon, $birthday);
$stmt->fetch();
$stmt->close();

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = $_POST['name'];
    $new_surname = $_POST['surname'];
    $new_email = $_POST['email'];
    $new_telefon = $_POST['telefon'];
    $new_birthday = $_POST['birthday'];

    $update_stmt = $conn->prepare("UPDATE users SET name_users = ?, surname_users = ?, mail_users = ?, telefon = ?, birthday_users = ? WHERE id_users = ?");
    $update_stmt->bind_param("sssssi", $new_name, $new_surname, $new_email, $new_telefon, $new_birthday, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    header("Location: account.php"); // Güncellemeden sonra sayfayı yenile
    exit();
}

// Şifre değişikliği
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Mevcut şifreyi kontrol et
    $stmt = $conn->prepare("SELECT password_users FROM users WHERE id_users = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Şifreyi doğrula
    if (password_verify($current_password, $hashed_password) && $new_password === $confirm_password) {
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password_stmt = $conn->prepare("UPDATE users SET password_users = ? WHERE id_users = ?");
        $update_password_stmt->bind_param("si", $new_hashed_password, $user_id);
        if($update_password_stmt->execute()){
            $updateMessage = 'success'; // Başarı durumunda
        }
        else{
            $updateMessage = 'error'; // Hata durumunda
        }
        $update_password_stmt->close();
        header("Location: account.php"); // Güncellemeden sonra sayfayı yenile
        exit();
    } else {
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/account.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Account</title>
    <style>
        .edit-icon {
            cursor: pointer;
            color: #007bff;
            font-size: 1.2em;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo text-center mb-4">
            <img src="/hasgenesis/images/logo-empty.png" alt="Logo" style="width: 80%;">
        </div>
        <nav class="nav flex-column">
            <a href="#profile" class="nav-link active" data-bs-toggle="tab"><i class='bx bxs-user'></i> Profil</a>
            <a href="#change-password" class="nav-link" data-bs-toggle="tab"><i class='bx bxs-lock'></i> Şifre Değiştir</a>
            <a href="#settings" class="nav-link" data-bs-toggle="tab"><i class='bx bxs-cog'></i> Ayarlar</a>
            <a href="#logout" class="nav-link"><i class='bx bxs-log-out'></i> Çıkış Yap</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="tab-content">
            <!-- Profile Tab -->
            <div class="tab-pane fade show active" id="profile">
                <h2>Profil Bilgileri <i class="bx bx-edit edit-icon" onclick="toggleEditProfile()"></i></h2>
                <div id="profile-info">
                    <p><strong>Ad:</strong> <span id="user-name"><?php echo htmlspecialchars($name); ?></span></p>
                    <p><strong>Soyad:</strong> <span id="user-surname"><?php echo htmlspecialchars($surname); ?></span></p>
                    <p><strong>E-posta:</strong> <span id="user-email"><?php echo htmlspecialchars($email); ?></span></p>
                    <p><strong>Telefon:</strong> <span id="user-telefon"><?php echo htmlspecialchars($telefon); ?></span></i></p>
                    <p><strong>Doğum Tarihi:</strong> <span id="user-birthday"><?php echo htmlspecialchars($birthday); ?></span></i></p>
                </div>
                <form id="edit-profile-form" style="display: none;" method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Ad</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="surname" class="form-label">Soyad</label>
                        <input type="text" class="form-control" id="surname" name="surname" value="<?php echo htmlspecialchars($surname); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefon" class="form-label">Telefon</label>
                        <input type="text" class="form-control" id="telefon" name="telefon" value="<?php echo htmlspecialchars($telefon); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="birthday" class="form-label">Doğum Tarihi</label>
                        <input type="date" class="form-control" id="birthday" name="birthday" value="<?php echo htmlspecialchars($birthday); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Profili Güncelle</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleEditProfile()">İptal</button>
                </form>
            </div>
            <!-- Change Password Tab -->
            <div class="tab-pane fade" id="change-password">
                <h2>Şifre Değiştir</h2>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mevcut Şifre</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Mevcut şifrenizi girin" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Yeni şifrenizi girin" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Yeni Şifreyi Onaylayın</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Yeni şifrenizi tekrar girin" required>
                    </div>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    <button type="submit" name="change_password" class="btn btn-primary">Şifreyi Güncelle</button>
                </form>
            </div>
            <!-- Settings Tab -->
            <div class="tab-pane fade" id="settings">
                <h2>Ayarlar</h2>
                <p>Buraya ayarlar bilgileri gelecek...</p>
            </div>
            <!-- Logout Tab -->
            <div class="tab-pane fade" id="logout">
                <h2>Çıkış Yap</h2>
                <p>Çıkış yapmak için tıklayın...</p>
            </div>
        </div>
    </div>

    <script>
        // Profil düzenleme fonksiyonu
        function toggleEditProfile() {
            const profileInfo = document.getElementById('profile-info');
            const editProfileForm = document.getElementById('edit-profile-form');
            if (editProfileForm.style.display === 'none') {
                profileInfo.style.display = 'none';
                editProfileForm.style.display = 'block';
            } else {
                profileInfo.style.display = 'block';
                editProfileForm.style.display = 'none';
            }
        }
    </script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <script>
        // Profil güncelleme mesajı
        <?php if ($updateMessage === 'success'): ?>
            swal("Başarılı!", "Profil bilgileri başarıyla güncellendi.", "success");
        <?php elseif ($updateMessage === 'error'): ?>
            swal("Hata!", "Profil güncellenirken bir hata oluştu.", "error");
        <?php endif; ?>

        // Şifre değiştirme mesajı
        <?php if ($passwordMessage === 'success'): ?>
            swal("Başarılı!", "Şifre başarıyla değiştirildi.", "success");
        <?php elseif ($passwordMessage === 'error'): ?>
            swal("Hata!", "Şifre değiştirilirken bir hata oluştu.", "error");
        <?php endif; ?>
    </script>
</body>
</html>
