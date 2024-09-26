<?php
session_start();
session_destroy();
header("Location: /hasgenesis/index.php");
exit();
?>