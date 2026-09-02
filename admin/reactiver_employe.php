<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Accès refusé.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: dashboard.php?page=employes&error=invalide');
    exit();
}

$stmt = $pdo->prepare(
    "UPDATE utilisateurs u
     INNER JOIN employes e ON e.utilisateur_id = u.id
     SET u.statut = 'actif'
     WHERE e.id = ?"
);
$stmt->execute([$id]);

header('Location: dashboard.php?page=employes&status=reactive');
exit();
