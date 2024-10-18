<?php
include 'dB/database.php'; 

// POST verilerini al
$bicycle_id = $_POST['bicycle_id'];
$organization_id = $_POST['organization_id'];

// Organizasyonun süspansiyon değerlerini al
$organization_query = "SELECT min_front_suspension_travel, min_rear_suspension_travel FROM organizations WHERE id = ?";
$org_stmt = $conn->prepare($organization_query);
$org_stmt->bind_param("i", $organization_id);
$org_stmt->execute();
$org_stmt->bind_result($min_front_suspension_travel, $min_rear_suspension_travel);

if (!$org_stmt->fetch()) {
    // Organizasyon bulunamazsa hata mesajı döndür
    $org_stmt->close();
    $conn->close();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Organization not found.']);
    exit;
}

$org_stmt->close();

// Bisikletin süspansiyon değerlerini al
$query = "SELECT front_travel, rear_travel FROM bicycles WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bicycle_id);
$stmt->execute();
$stmt->bind_result($front_travel, $rear_travel);

$response = ['isValid' => false]; // Varsayılan olarak geçersiz

if ($stmt->fetch()) {
    // Süspansiyon değerlerini kontrol et
    if ($front_travel >= $min_front_suspension_travel && $rear_travel >= $min_rear_suspension_travel) {
        $response['isValid'] = true; // Geçerli
    }
}

$stmt->close();
$conn->close();

// JSON yanıtı döndür
header('Content-Type: application/json');
echo json_encode($response);
?>
