<?php
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "hasgenesis";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Bağlantı başarısız: " . $conn->connect_error);
}   
?>