<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$id = $_GET['id'];

$sql = "UPDATE notifications
        SET lu = 1
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

header("Location: dashboard.php?page=notifications");
exit();

?>