<?php

require_once "../config/auth.php";
require_once "../config/database.php";


$id = $_GET['id'];



/* Vérifier s'il y a des employés */

$sql = "SELECT COUNT(*) AS total
        FROM employes
        WHERE division_id = ?";


$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$resultat = $stmt->fetch();



if($resultat['total'] > 0)
{
    header("Location: dashboard.php?page=divisions&error=employes");
    exit();
}



/* Supprimer la division */

$sql = "DELETE FROM divisions
        WHERE id = ?";


$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);



header("Location: dashboard.php?page=divisions");
exit();

?>