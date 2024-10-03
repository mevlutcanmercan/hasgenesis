<?php
include 'dB/database.php';
include 'navbar.php'; 
include 'bootstrap.php';
include 'auth.php';

requireLogin(); // Kullanıcının giriş yapıp yapmadığını kontrol eder

$user_id = $_SESSION['id_users']; 

// Kullanıcı bilgilerini al
$stmt = $conn->prepare("SELECT mail_users, name_users, surname_users, telefon, birthday_users, isAdmin FROM users WHERE id_users = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email, $name, $surname, $telefon, $birthday, $isAdmin);
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
    
    if ($update_stmt->execute()) {
        $_SESSION['message'] = "success|Profil başarıyla güncellendi!";
    } else {
        $_SESSION['message'] = "error|Profil güncellenirken bir hata oluştu.";
    }
    $update_stmt->close();

    // JavaScript ile yönlendirme
    echo "<script>
        window.onload = function() {
            setTimeout(function() {
                window.location.href = 'account.php';
            }, 500);
        };
    </script>";
}

// Şifre değişikliği
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password_users FROM users WHERE id_users = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    if (password_verify($current_password, $hashed_password)) {
        if ($new_password === $confirm_password) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password_stmt = $conn->prepare("UPDATE users SET password_users = ? WHERE id_users = ?");
            $update_password_stmt->bind_param("si", $new_hashed_password, $user_id);
            if($update_password_stmt->execute()){
                $_SESSION['message'] = "success|Şifre başarıyla güncellendi!";
            } else {
                $_SESSION['message'] = "error|Şifre güncellenirken bir hata oluştu.";
            }
            $update_password_stmt->close();
        } else {
            $_SESSION['message'] = "error|Yeni şifreler uyuşmuyor.";
        }
    } else {
        $_SESSION['message'] = "error|Mevcut şifre yanlış.";
    }

    // JavaScript ile yönlendirme
    echo "<script>
        window.onload = function() {
            setTimeout(function() {
                window.location.href = 'account.php';
            }, 500);
        };
    </script>";
}

// Bisiklet markalarını almak için
$brands_query = "SELECT id, brandName FROM brands";
$brands_result = $conn->query($brands_query);

// Bisiklet ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bicycle'])) {
    $brand_id = $_POST['brand'];
    $front_travel = $_POST['front_travel'];
    $rear_travel = $_POST['rear_travel'];

    $insert_bike_stmt = $conn->prepare("INSERT INTO bicycles (brand, front_travel, rear_travel, user_id) VALUES (?, ?, ?, ?)");
    $insert_bike_stmt->bind_param("iiii", $brand_id, $front_travel, $rear_travel, $user_id);

    if ($insert_bike_stmt->execute()) {
        $_SESSION['message'] = "success|Bisiklet başarıyla eklendi!";
    } else {
        $_SESSION['message'] = "error|Bisiklet eklenirken bir hata oluştu.";
    }
    $insert_bike_stmt->close();

    // JavaScript ile yönlendirme
    echo "<script>
        window.onload = function() {
            setTimeout(function() {
                window.location.href = 'account.php';
            }, 500);
        };
    </script>";
}

// Kullanıcının eklediği bisikletleri çekmek için
$user_bikes_query = "SELECT b.id, br.brandName, b.front_travel, b.rear_travel 
                     FROM bicycles b
                     JOIN brands br ON b.brand = br.id
                     WHERE b.user_id = ?";
$user_bikes_stmt = $conn->prepare($user_bikes_query);
$user_bikes_stmt->bind_param("i", $user_id);
$user_bikes_stmt->execute();
$user_bikes_result = $user_bikes_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/account.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <!-- SweetAlert mesajlarını göstermek için -->
    <?php if (isset($_SESSION['message'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                let message = "<?php echo $_SESSION['message']; ?>";
                let messageParts = message.split("|");
                let alertType = messageParts[0];
                let alertMessage = messageParts[1];

                if (alertType === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: alertMessage,
                        confirmButtonText: 'Tamam'
                    });
                } else if (alertType === "error") {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: alertMessage,
                        confirmButtonText: 'Tamam'
                    });
                }

                <?php unset($_SESSION['message']); ?> // Mesajı oturumdan temizle
            });
        </script>
    <?php endif; ?>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo text-center mb-4">
            <img src="/images/logo-empty.png" alt="Logo" style="width: 80%;">
            <button id="toggle-sidebar" class="btnCollapse"><i class='bx bx-collapse-horizontal'></i></button>
        </div>
        <nav class="nav flex-column">
            <a href="#profile" class="nav-link active" data-bs-toggle="tab"><i class='bx bxs-user'></i> Profil</a>
            <a href="#change-password" class="nav-link" data-bs-toggle="tab"><i class='bx bxs-lock'></i> Şifre Değiştir</a>
            <a href="#bicycle" class="nav-link" data-bs-toggle="tab"><i class="bi bi-bicycle"></i> Bisikletlerim</a>

            <!-- Admin Tab: Eğer kullanıcı admin ise göster -->
            <?php if ($isAdmin == 1): ?>
                <a href="#admin-panel" class="nav-link" data-bs-toggle="tab"><i class='bx bxs-shield'></i> Admin Paneli</a>
            <?php endif; ?>
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
                    <p><strong>Doğum Tarihi:</strong> <span id="user-birthday"><?php echo htmlspecialchars($birthday); ?></span></p>
                </div>

                <div id="edit-profile-form" style="display: none;">
                    <form method="POST" action="">
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
                        <button type="submit" name="update_profile" class="btn btn-primary">Güncelle</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleEditProfile()">İptal</button>
                    </form>
                </div>
            </div>

            <!-- Change Password Tab -->
            <div class="tab-pane fade" id="change-password">
                <h2>Şifre Değiştir</h2>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mevcut Şifre</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Yeni Şifreyi Onayla</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Değiştir</button>
                </form>
            </div>

            <!-- Bicycles Tab -->
            <div class="tab-pane fade" id="bicycle">
                <h2>Bisikletlerim</h2>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="brand">Marka</label>
                        <select class="form-select" id="brand" name="brand" required>
                            <option value="">Marka Seçin</option>
                            <?php while ($row = $brands_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['brandName']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="front_travel">Ön Süspansiyon Yolu (mm)</label>
                        <input type="number" class="form-control" id="front_travel" name="front_travel" required>
                    </div>
                    <div class="mb-3">
                        <label for="rear_travel">Arka Süspansiyon Yolu (mm)</label>
                        <input type="number" class="form-control" id="rear_travel" name="rear_travel" required>
                    </div>
                    <button type="submit" name="add_bicycle" class="btn btn-primary">Bisiklet Ekle</button>
                </form>
                <h3>Eklediğiniz Bisikletler</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Marka</th>
                            <th>Ön Süspansiyon Yolu (mm)</th>
                            <th>Arka Süspansiyon Yolu (mm)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bike = $user_bikes_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bike['brandName']); ?></td>
                                <td><?php echo htmlspecialchars($bike['front_travel']); ?></td>
                                <td><?php echo htmlspecialchars($bike['rear_travel']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Admin Panel Tab -->
            <?php if ($isAdmin == 1): ?>
            <div class="tab-pane fade" id="admin-panel">
                <h2>Admin Paneli</h2>
                <p>Burada admin paneli için içerik olacak.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleEditProfile() {
            var profileInfo = document.getElementById('profile-info');
            var editForm = document.getElementById('edit-profile-form');
            if (profileInfo.style.display === "none") {
                profileInfo.style.display = "block";
                editForm.style.display = "none";
            } else {
                profileInfo.style.display = "none";
                editForm.style.display = "block";
            }
        }
    </script>

<script>
    document.getElementById('toggle-sidebar').addEventListener('click', function() {
        var sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('collapsed');

        // İkonun durumunu değiştirme
        var icon = this.querySelector('i');
        if (sidebar.classList.contains('collapsed')) {
            icon.classList.remove('bx-collapse-horizontal');
            icon.classList.add('bx-expand-horizontal'); // İkonu değiştir
        } else {
            icon.classList.remove('bx-expand-horizontal');
            icon.classList.add('bx-collapse-horizontal'); // İkonu geri al
        }
    });
</script>

</body>
</html>
