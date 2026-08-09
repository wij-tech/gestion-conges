<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if ($_SESSION['role'] != "chef_division") {
    die("Accès refusé.");
}

$id_conge = $_GET['id'];


// Informations du chef connecté

$sql = "SELECT * FROM employes
        WHERE utilisateur_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['id']]);

$chef = $stmt->fetch();


// Informations du congé

$sql = "SELECT * FROM conges
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_conge]);

$conge = $stmt->fetch();


// Mise à jour du statut

$sql = "UPDATE conges

        SET

        statut = 'Refuse',
        chef_division_id = ?,
        date_traitement = NOW()

        WHERE id = ?";


$stmt = $pdo->prepare($sql);

$stmt->execute([

    $chef['id'],
    $id_conge

]);


// Notification

$sql = "SELECT utilisateur_id
        FROM employes
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$conge['employe_id']]);

$employe = $stmt->fetch();


$sql = "INSERT INTO notifications(

        utilisateur_id,
        titre,
        message,
        type_notification,
        lu

        )

        VALUES(?, ?, ?, ?, 0)";


$stmt = $pdo->prepare($sql);

$stmt->execute([

    $employe['utilisateur_id'],
    "Congé refusé",
    "Votre demande de congé a été refusée.",
    "Congé"

]);


header("Location: dashboard.php?page=demandes_division");
exit();

?>