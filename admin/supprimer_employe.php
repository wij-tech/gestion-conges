<?php
require_once "../config/auth.php";
require_once "../config/database.php";

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Accès refusé.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Session du formulaire expirée.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: dashboard.php?page=employes&error=invalide');
    exit();
}


/*
Récupérer l'id utilisateur
*/

$sql = "SELECT utilisateur_id
        FROM employes
        WHERE id = ?";


$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$employe = $stmt->fetch();
if (!$employe) {
    header('Location: dashboard.php?page=employes&error=introuvable');
    exit();
}

$utilisateur_id = $employe['utilisateur_id'];

try {
    $pdo->beginTransaction();

    $stmtConges = $pdo->prepare('SELECT COUNT(*) FROM conges WHERE employe_id = ?');
    $stmtConges->execute([$id]);
    if ((int) $stmtConges->fetchColumn() > 0) {
        $pdo->rollBack();
        header('Location: dashboard.php?page=employes&error=conges');
        exit();
    }

    $pdo->prepare('DELETE FROM notifications WHERE utilisateur_id = ?')->execute([$utilisateur_id]);
    $pdo->prepare('DELETE FROM employes WHERE id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM utilisateurs WHERE id = ?')->execute([$utilisateur_id]);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: dashboard.php?page=employes&error=suppression');
    exit();
}


header("Location: dashboard.php?page=employes");
exit();

?>
