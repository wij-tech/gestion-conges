<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "../config/auth.php";
require_once "../config/database.php";


$id = $_GET['id'];


/*
Récupérer l'id utilisateur
*/

$sql = "SELECT utilisateur_id
        FROM employes
        WHERE id = ?";


$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$employe = $stmt->fetch();

$utilisateur_id = $employe['utilisateur_id'];


/*
Supprimer l'employé
*/

$sql = "DELETE FROM employes
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);


/*
Supprimer son compte utilisateur
*/

$sql = "DELETE FROM utilisateurs
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$utilisateur_id]);


header("Location: dashboard.php?page=employes");
exit();

?>