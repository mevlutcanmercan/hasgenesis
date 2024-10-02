<?php
include 'dB/database.php';
include 'navbar.php'; 
include 'bootstrap.php';
include 'auth.php';

requireLogin();

$user_id = $_SESSION['id_users']; 
// Giriş kontrolü
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
        $bikeMessage = 'success'; // Başarı durumu
    } else {
        $bikeMessage = 'error'; // Hata durumu
    }
    $insert_bike_stmt->close();
    header("Location: account.php");
    exit();
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
             <!-- Sidebar toggle butonu -->
        <button id="toggle-sidebar" class="btnCollapse"><i class='bx bx-collapse-horizontal'></i></button>
        </div>
        <nav class="nav flex-column">
            <a href="#profile" class="nav-link active" data-bs-toggle="tab"><i class='bx bxs-user'></i> Profil</a>
            <a href="#change-password" class="nav-link" data-bs-toggle="tab"><i class='bx bxs-lock'></i> Şifre Değiştir</a>
            <a href="#bicycle" class="nav-link" data-bs-toggle="tab"><i class="bi bi-bicycle"></i> Bisikletlerim </a>

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
                    
                    <div class="tab-pane fade" id="bicycle">
            <h2>Bisikletlerim</h2>

                <!-- Bisiklet Ekleme Formu -->
        <form method="POST" action="" id="bicycleForm">
            <div class="mb-3">
                <label for="brand" class="form-label">Bisiklet Markası</label>
                <select class="form-control" id="brand" name="brand" required>
                    <option value="" disabled selected>Marka Seçin</option>
                    <?php while ($row = $brands_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['brandName']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="front_travel" class="form-label">Ön Süspansiyon (mm)</label>
                <input type="number" class="form-control" id="front_travel" name="front_travel" min="80" max="220" required>
                <small>Ön süspansiyon 80 ile 220 mm arasında olmalıdır.</small>
            </div>
            <div class="mb-3">
                <label for="rear_travel" class="form-label">Arka Süspansiyon (mm)</label>
                <input type="number" class="form-control" id="rear_travel" name="rear_travel" min="80" max="220" required>
                <small>Arka süspansiyon 80 ile 220 mm arasında olmalıdır.</small>
            </div>
            <button type="submit" name="add_bicycle" class="btn btn-primary">Bisikleti Ekle</button>
        </form>

            <!-- Kullanıcının Eklediği Bisikletler -->
            <h3>Eklenen Bisikletler</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Marka</th>
                        <th>Ön Süspansiyon (mm)</th>
                        <th>Arka Süspansiyon (mm)</th>
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
            <p>zort.</p>
        </div>
        <?php endif; ?>

           
        </div>
    </div>

    <script>

                    // Bisiklet ekleme mesajları
        <?php if ($bikeMessage === 'success'): ?>
            swal("Başarılı!", "Bisiklet başarıyla eklendi.", "success");
        <?php elseif ($bikeMessage === 'error'): ?>
            swal("Hata!", "Bisiklet eklenirken bir hata oluştu.", "error");
        <?php endif; ?>

            document.getElementById('bicycleForm').addEventListener('submit', function(event) {
        const frontTravel = document.getElementById('front_travel').value;
        const rearTravel = document.getElementById('rear_travel').value;

        if (frontTravel < 80 || frontTravel > 220) {
            alert("Ön süspansiyon 80 ile 220 mm arasında olmalıdır.");
            event.preventDefault(); // Formun gönderilmesini engeller
        }

        if (rearTravel < 80 || rearTravel > 220) {
            alert("Arka süspansiyon 80 ile 220 mm arasında olmalıdır.");
            event.preventDefault(); // Formun gönderilmesini engeller
        }
    });


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
    <script>
    // Sidebar'ı daraltma/genişletme fonksiyonu
    document.getElementById('toggle-sidebar').addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        const content = document.querySelector('.content');
        const btnCollapse = document.querySelector('.btnCollapse');
        
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('collapsed');
        btnCollapse.classList.toggle('collapsed');
    });
</script>

</body>
</html>
