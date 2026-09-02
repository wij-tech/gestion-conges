<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Accès refusé.');
}

$idConge = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$idConge || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide.');
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE conges SET statut = 'refusee', date_traitement = NOW() WHERE id = ? AND statut = 'en_attente_admin'");
    $stmt->execute([$idConge]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Demande déjà traitée ou introuvable.');
    }

    $stmt = $pdo->prepare('SELECT conges.employe_id, employes.utilisateur_id FROM conges INNER JOIN employes ON employes.id = conges.employe_id WHERE conges.id = ?');
    $stmt->execute([$idConge]);
    $demande = $stmt->fetch();
    $stmt = $pdo->prepare('INSERT INTO notifications (utilisateur_id, titre, message, type_notification, lu) VALUES (?, ?, ?, ?, 0)');
    $stmt->execute([$demande['utilisateur_id'], 'Congé refusé', 'Votre demande de congé a été refusée par l’administrateur.', 'Congé']);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

header('Location: dashboard.php?page=conges');
exit();
