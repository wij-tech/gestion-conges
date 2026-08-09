<?php

require_once "../config/auth.php";
require_once "../config/database.php";


if($_SESSION['role'] != 'admin'){
    die("Accès refusé.");
}


$id = $_GET['id'];



$sql = "DELETE FROM notifications
        WHERE id = ?";


$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);



header("Location: dashboard.php?page=notifications");
exit();

?>