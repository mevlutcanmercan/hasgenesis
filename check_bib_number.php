<?php
include 'dB/database.php'; // Veritabanı bağlantısını dahil edin

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bib_number = intval($_POST['bib_number']);
    $organization_id = intval($_POST['organization_id']);

    // Bib numarasının varlığını kontrol et
    $query = "SELECT Bib FROM registrations WHERE Bib = ? AND organization_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $bib_number, $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Sonuçları kontrol et
    $isValid = $result->num_rows == 0; // Eğer kayıtlı değilse geçerli

    // JSON formatında geri dön
    echo json_encode(['isValid' => $isValid]);
}
?>
