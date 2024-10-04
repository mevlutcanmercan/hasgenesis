<?php
include '../db/database.php'; // Veritabanı bağlantısını dahil et
include 'sidebar.php'; // Yan menüyü dahil et

$message_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($message_id <= 0) {
    die("Geçersiz mesaj ID'si.");
}

// Mesaj detaylarını al
$message_query = "SELECT * FROM communication WHERE id = $message_id";
$message_result = $conn->query($message_query);
$message = $message_result->fetch_assoc();

if (!$message) {
    die("Mesaj bulunamadı.");
}

// Filtre ayarlarını al
$is_user_filter = isset($_GET['is_user']) ? $_GET['is_user'] : '';
$topic_filter = isset($_GET['topic']) ? $_GET['topic'] : '';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesaj Detayları</title>
    <link rel="stylesheet" href="admincss/message-details.css">
    <script>
        // E-posta gönderme fonksiyonu
        function sendEmail(email) {
            const subject = encodeURIComponent("HasGenesis Hk."); // E-posta konusu
            const body = encodeURIComponent("Merhaba,\n\n"); // E-posta içeriği
            window.location.href = `mailto:${email}?subject=${subject}&body=${body}`; // E-posta istemcisini aç
        }
    </script>
</head>
<body>

<div class="message-container">
    <h2>Mesaj Detayları</h2>
    
    <div class="message-detail">
        <div class="detail-row">
            <h3>İsim:</h3>
            <p><?php echo htmlspecialchars($message['name']); ?></p>
        </div>
        
        <div class="detail-row">
            <h3>Soyisim:</h3>
            <p><?php echo htmlspecialchars($message['surname']); ?></p>
        </div>
        
        <div class="detail-row">
            <h3>Şirket:</h3>
            <p><?php echo htmlspecialchars($message['company']); ?></p>
        </div>
        
        <div class="detail-row">
            <h3>Konu:</h3>
            <p><?php echo htmlspecialchars($message['topic']); ?></p>
        </div>
        
        <div class="detail-row">
            <h3>Telefon Numarası:</h3>
            <p><?php echo htmlspecialchars($message['phone_number']); ?></p>
        </div>
        
        <div class="detail-row">
            <h3>E-posta:</h3>
            <p><?php echo htmlspecialchars($message['mail']); ?></p>
        </div>

        <div class="detail-row">
            <h3>Tarih:</h3>
            <p><?php echo htmlspecialchars($message['created_at']); ?></p>
        </div>

        <div class="detail-row">
            <h3>Mesaj:</h3>
            <div class="message-text"><?php echo nl2br(htmlspecialchars($message['text'])); ?></div>
        </div>
    </div>

    <a class="back-button" href="communication-management.php?is_user=<?php echo urlencode($is_user_filter); ?>&topic=<?php echo urlencode($topic_filter); ?>">Geri Dön</a>
    <button class="email-button" onclick="sendEmail('<?php echo htmlspecialchars($message['mail']); ?>')">E-posta Gönder</button>
</div>

</body>
</html>
