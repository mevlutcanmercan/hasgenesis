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

    // Fotoğrafın yüklendiğini kontrol et
    if (isset($_FILES['profile-photo']) && $_FILES['profile-photo']['error'] === UPLOAD_ERR_OK) {
        $photo_tmp_name = $_FILES['profile-photo']['tmp_name'];
        $photo_name = $_FILES['profile-photo']['name'];
        $photo_target_dir = "images/profilephotos/";
        $photo_target_file = $photo_target_dir . basename($photo_name);
        $imageFileType = strtolower(pathinfo($photo_target_file, PATHINFO_EXTENSION));

        // Dosya türünü kontrol et
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_types)) {
            // Fotoğrafı sunucuya kaydet
            if (move_uploaded_file($photo_tmp_name, $photo_target_file)) {
                // Profil fotoğrafı güncellemesi için SQL sorgusu
                $update_stmt = $conn->prepare("UPDATE users SET name_users = ?, surname_users = ?, mail_users = ?, telefon = ?, birthday_users = ?, profile_photo_path = ? WHERE id_users = ?");
                $update_stmt->bind_param("ssssssi", $new_name, $new_surname, $new_email, $new_telefon, $new_birthday, $photo_name, $user_id);
            } else {
                echo "Fotoğraf yüklenirken bir hata oluştu.";
            }
        } else {
            echo "Sadece JPG, JPEG, PNG ve GIF dosyalarına izin verilmektedir.";
        }
    } else {
        // Eğer yeni fotoğraf yüklenmemişse, sadece diğer bilgileri güncelle
        $update_stmt = $conn->prepare("UPDATE users SET name_users = ?, surname_users = ?, mail_users = ?, telefon = ?, birthday_users = ? WHERE id_users = ?");
        $update_stmt->bind_param("sssssi", $new_name, $new_surname, $new_email, $new_telefon, $new_birthday, $user_id);
    }

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_photo'])) {
    // Yüklenen dosyayı kontrol et
    if (isset($_FILES['profile-photo']) && $_FILES['profile-photo']['error'] === UPLOAD_ERR_OK) {
        // Dosya bilgilerini al
        $photo_tmp_name = $_FILES['profile-photo']['tmp_name'];
        $photo_name = $_FILES['profile-photo']['name'];
        $photo_target_dir = "images/profilephotos/";
        $photo_target_file = $photo_target_dir . basename($photo_name);
        $imageFileType = strtolower(pathinfo($photo_target_file, PATHINFO_EXTENSION));

        // İzin verilen dosya türleri
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_types)) {
            // Dosyayı yükle
            if (move_uploaded_file($photo_tmp_name, $photo_target_file)) {
                // Eski profil fotoğrafını güncelle
                $stmt = $conn->prepare("UPDATE users SET profile_photo_path = ? WHERE id_users = ?");
                $stmt->bind_param("si", $photo_name, $user_id);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "success|Profil fotoğrafı başarıyla eklendi!";
                } else {
                    $_SESSION['message'] = "error|Profil fotoğrafı güncellenirken bir hata oluştu.";
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "error|Fotoğraf yüklenirken bir hata oluştu.";
            }
        } else {
            $_SESSION['message'] = "error|Sadece JPG, JPEG, PNG ve GIF dosyalarına izin verilmektedir.";
        }
    } else {
        $_SESSION['message'] = "error|Fotoğraf seçilmedi veya yüklenirken bir hata oluştu.";
    }

    // Sayfanın yeniden yüklenmesi
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

    echo "<script>
        window.onload = function() {
            setTimeout(function() {
                window.location.href = 'account.php';
            }, 500);
        };
    </script>";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_bicycle'])) {
    $bike_id = $_POST['bike_id'];

    $delete_bike_stmt = $conn->prepare("DELETE FROM bicycles WHERE id = ? AND user_id = ?");
    $delete_bike_stmt->bind_param("ii", $bike_id, $user_id);

    if ($delete_bike_stmt->execute()) {
        $_SESSION['message'] = "success|Bisiklet başarıyla silindi!";
    } else {
        $_SESSION['message'] = "error|Bisiklet silinirken bir hata oluştu.";
    }
    $delete_bike_stmt->close();

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
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"> <!-- Boxicons -->
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
        <div class="sidebar" id="sidebar">
        <div class="logo">
          <?php
            // Profil fotoğrafı yolunu al
            $stmt = $conn->prepare("SELECT profile_photo_path FROM users WHERE id_users = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($profile_photo_path);
            $stmt->fetch();
            $stmt->close();
            
            // Eğer profil fotoğrafı yoksa varsayılan bir resim göster
            $profile_photo = !empty($profile_photo_path) ? "images/profilephotos/" . htmlspecialchars($profile_photo_path) : 'images/logo-has.png';
            ?>
            <img src="<?php echo $profile_photo; ?>" alt="Profil Fotoğrafı" class="img-fluid">
            <!-- Profil fotoğrafını değiştirmek için form -->
                    <form action="" method="post" enctype="multipart/form-data">
            <input type="file" name="profile-photo" accept="image/*">
            <button type="submit" name="update_profile_photo" class="btn">Ekle</button>
        </form>

            <hr class="">
        </div>
           
               <nav class="nav flex-column">
                <a href="#profile" class="nav-link active" data-bs-toggle="tab"><i class='bx bxs-user'></i> Profil</a>
                <a href="#change-password" class="nav-link" data-bs-toggle="tab"><i class='bx bxs-lock'></i> Şifre Değiştir</a>
                <a href="#bicycle" class="nav-link" data-bs-toggle="tab"><i class='bx bx-trip'></i> Bisikletlerim</a>

                <!-- Admin Tab: Eğer kullanıcı admin ise göster -->
                  <?php if ($isAdmin == 1): ?>
             <a href="admin/adminmainpage.php" class="nav-link"><i class='bx bxs-shield'></i> Admin Paneli</a>
            <?php endif; ?>
            </nav>
        </div>
    
                <!-- Toggle Icon (Sidebar aç/kapa) -->
        <div class="toggle-icon" id="toggle-icon" >
            <i class='bx bx-chevrons-left' ></i> <!-- Sol ok ikonu (açık) -->
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
                    <form method="POST" action="" enctype="multipart/form-data">
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
                        <div class="mb-3">
                            <label for="profile-photo" class="form-label">Profil Fotoğrafı</label>
                            <input type="file" class="form-control" id="profile-photo" name="profile-photo" accept="image/*">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Güncelle</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleEditProfile()">İptal</button>
                    </form>
                </div>
            </div>
        <!-- Change Password Tab -->
        <div class="tab-pane fade" id="change-password">
            <h2>Şifre Değiştir</h2>
            <form method="POST" action="" onsubmit="return validateChangePassword()">
                <div class="mb-3">
                    <label for="current_password" class="form-label">Mevcut Şifre</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">Yeni Şifre</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <small id="new-password-error" style="color:red; display:none;">Şifre en az 7 karakter olmalı ve harf+sayı içermelidir.</small>
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
                <label for="front_travel" class="form-label">Ön Süspansiyon (mm)</label>
                <input type="number" class="form-control" id="front_travel" name="front_travel" min="80" max="220" required>
                <small>Ön süspansiyon 80 ile 220 mm arasında olmalıdır.</small>
            </div>
            <div class="mb-3">
                <label for="rear_travel" class="form-label">Arka Süspansiyon (mm)</label>
                <input type="number" class="form-control" id="rear_travel" name="rear_travel" min="80" max="220" required>
                <small>Arka süspansiyon 80 ile 220 mm arasında olmalıdır.</small>
            </div>
                    <button type="submit" name="add_bicycle" class="btn add-bike">Bisiklet Ekle</button>
                </form>
                <h3>Eklediğiniz Bisikletler</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Marka</th>
                            <th>Ön Süspansiyon Yolu (mm)</th>
                            <th>Arka Süspansiyon Yolu (mm)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bike = $user_bikes_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bike['brandName']); ?></td>
                                <td><?php echo htmlspecialchars($bike['front_travel']); ?></td>
                                <td><?php echo htmlspecialchars($bike['rear_travel']); ?></td>
                                <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="bike_id" value="<?php echo $bike['id']; ?>">
                                    <button type="submit" name="delete_bicycle" class="btn btn-danger" onclick="return confirm('Bu bisikleti silmek istediğinize emin misiniz?')">Sil</button>
                                </form>
                            </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

     <script>
    function validateChangePassword() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const newPasswordError = document.getElementById('new-password-error');
        const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{7,}$/;

        if (!passwordPattern.test(newPassword)) {
            newPasswordError.style.display = 'block';
            newPasswordError.textContent = "Şifre en az 7 karakter olmalı ve harf+sayı içermelidir.";
            return false;
        } else if (newPassword !== confirmPassword) {
            newPasswordError.style.display = 'block';
            newPasswordError.textContent = "Yeni şifreler uyuşmuyor.";
            return false;
        } else {
            newPasswordError.style.display = 'none';
            return true;
        }
    }
     </script>
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
    const toggleSidebarBtn = document.getElementById('toggle-icon');
    const sidebar = document.getElementById('sidebar');
    const content = document.querySelector('.content');
    let isOpen = true;

    toggleSidebarBtn.addEventListener('click', () => {
        if (isOpen) {
            sidebar.style.transform = 'translateX(-100%)';
            toggleSidebarBtn.style.left = '10px'; // Sidebar kapandığında buton sola yaklaşır
            content.style.marginLeft = '0';
            toggleSidebarBtn.innerHTML = "<i class='bx bx-chevrons-right'></i>";
        } else {
            sidebar.style.transform = 'translateX(0)';
            if (window.innerWidth <= 768) {
                toggleSidebarBtn.style.left = '210px'; // Mobilde buton çok sağa gitmesin
            } else {
                toggleSidebarBtn.style.left = '270px'; // Masaüstü için normal mesafe
            }
            content.style.marginLeft = '250px';
            toggleSidebarBtn.innerHTML = "<i class='bx bx-chevrons-left'></i>";
        }
        isOpen = !isOpen;
    });
</script>

<script>
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

</script>

</body>
</html>