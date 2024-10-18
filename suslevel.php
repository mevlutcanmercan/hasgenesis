<?php
include 'dB/database.php'; 

// POST verilerini al
$bicycle_id = $_POST['bicycle_id'];
$organization_id = $_POST['organization_id'];

// Organizasyon bilgilerini al (örnek olarak sabit değerler kullanılıyor)
$min_front_suspension_travel = 100; // Örnek değer
$min_rear_suspension_travel = 100; // Örnek değer

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
