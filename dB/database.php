<?php
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "hasgenesis";

$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantı kontrolü
if ($conn->connect_error) {
    die("Bağlantı başarısız: " . $conn->connect_error);
}

// Karakter setini UTF-8 olarak ayarlama
$conn->set_charset("utf8");
?>
