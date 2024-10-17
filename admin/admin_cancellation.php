<?php
include '../dB/database.php';
include 'sidebar.php';

// İptal taleplerini al
$sql = "SELECT c.id AS cancellation_id, c.reason, c.is_approved, c.registration_id, r.first_name, r.second_name, o.name AS organization_name
        FROM cancellations c
        JOIN registrations r ON c.registration_id = r.id
        JOIN organizations o ON r.organization_id = o.id
        WHERE c.is_approved = 0";
$result = $conn->query($sql);

// İptal talebini onaylama veya reddetme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cancellation_id = intval($_POST['cancellation_id']);
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        // Onaylama işlemi: is_approved = 1 ve registrations tablosundan silme
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE cancellations SET is_approved = 1 WHERE id = ?");
            $stmt->bind_param("i", $cancellation_id);
            $stmt->execute();

            $stmt2 = $conn->prepare("DELETE FROM registrations WHERE id = ?");
            $stmt2->bind_param("i", $_POST['registration_id']);
            $stmt2->execute();

            $conn->commit();
            echo "<script>
                    Swal.fire({
                        title: 'Onaylandı!',
                        text: 'Kayıt başarıyla silindi ve iptal isteği onaylandı.',
                        icon: 'success'
                    }).then(() => {
                        window.location = 'admin_cancellations.php';
                    });
                  </script>";
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            echo "Hata: " . $conn->error;
        }
    } elseif ($action == 'deny') {
        $stmt = $conn->prepare("UPDATE cancellations SET is_approved = 2 WHERE id = ?");
        $stmt->bind_param("i", $cancellation_id);
        if ($stmt->execute()) {
            echo "<script>
                    Swal.fire({
                        title: 'Reddedildi!',
                        text: 'İptal isteği başarıyla reddedildi.',
                        icon: 'warning'
                    }).then(() => {
                        window.location = 'admin_cancellations.php';
                    });
                  </script>";
        } else {
            echo "Hata: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İptal Talepleri - Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="cancellation-container">
        <h2>İptal Talepleri</h2>
        <table>
            <thead>
                <tr>
                    <th>İsim Soyisim</th>
                    <th>Organizasyon</th>
                    <th>İptal Sebebi</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['second_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['organization_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                        <td>Beklemede</td>
                        <td>
                            <form action="admin_cancellation.php" method="post" id="action-form-<?php echo $row['cancellation_id']; ?>">
                                <input type="hidden" name="cancellation_id" value="<?php echo $row['cancellation_id']; ?>">
                                <input type="hidden" name="registration_id" value="<?php echo $row['registration_id']; ?>">
                                <button type="button" class="approve-button" onclick="confirmAction('approve', <?php echo $row['cancellation_id']; ?>)">Onayla</button>
                                <button type="button" class="deny-button" onclick="confirmAction('deny', <?php echo $row['cancellation_id']; ?>)">Reddet</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        function confirmAction(action, cancellation_id) {
            let form = document.getElementById('action-form-' + cancellation_id);
            let actionType = action === 'approve' ? 'Onaylamak' : 'Reddetmek';
            Swal.fire({
                title: `${actionType} İstiyor Musunuz?`,
                text: "Bu işlem geri alınamaz!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Evet, ' + actionType.toLowerCase(),
                cancelButtonText: 'Hayır'
            }).then((result) => {
                if (result.isConfirmed) {
                    let actionInput = document.createElement("input");
                    actionInput.setAttribute("type", "hidden");
                    actionInput.setAttribute("name", "action");
                    actionInput.setAttribute("value", action);
                    form.appendChild(actionInput);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>

<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        color: #333;
    }
    .cancellation-container {
        width: 80%;
        margin: auto;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    }
    h2 {
        text-align: center;
        color: #444;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    table, th, td {
        border: 1px solid #ddd;
    }
    th, td {
        padding: 12px;
        text-align: center;
    }
    th {
        background-color: #4CAF50;
        color: white;
    }
    tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    tr:hover {
        background-color: #ddd;
    }
    .approve-button, .deny-button {
        padding: 8px 12px;
        color: white;
        border: none;
        cursor: pointer;
        border-radius: 4px;
    }
    .approve-button {
        background-color: #4CAF50;
    }
    .deny-button {
        background-color: #f44336;
    }
</style>