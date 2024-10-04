<?php
include '../db/database.php'; // Veritabanı bağlantısını dahil et

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT name, surname, phone_number, mail, company, text FROM communication WHERE id = $id";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $message = $result->fetch_assoc();
        // Mesaj detaylarını HTML formatında döndür
        echo "<div class='modal-message'>
        <h3>Mesaj Detayı</h3>
        <p><strong>İsim:</strong> " . htmlspecialchars($message['name']) . "</p>
        <p><strong>Soyisim:</strong> " . htmlspecialchars($message['surname']) . "</p>
        <p><strong>Telefon Numarası:</strong> " . htmlspecialchars($message['phone_number']) . "</p>
        <p><strong>E-posta:</strong> " . htmlspecialchars($message['mail']) . "</p>
        <p><strong>Şirket:</strong> " . htmlspecialchars($message['company']) . "</p>
        <p><strong>Mesaj:</strong><br>" . nl2br(htmlspecialchars($message['text'])) . "</p>
        <button class='email-button' onclick=\"sendEmail('" . htmlspecialchars($message['mail']) . "')\">
            <i class='fas fa-envelope'></i> E-posta ile Dönüş Yap
        </button>
      </div>";

    } else {
        echo "Mesaj bulunamadı.";
    }
}
?>
