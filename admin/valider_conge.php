<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/solde_annuel.php";

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
    $stmt = $pdo->prepare('SELECT * FROM conges WHERE id = ? FOR UPDATE');
    $stmt->execute([$idConge]);
    $conge = $stmt->fetch();

    if (!$conge || $conge['statut'] !== 'en_attente_admin') {
        throw new RuntimeException('Demande déjà traitée ou introuvable.');
    }

    if ($conge['type_conge'] === 'Congé annuel') {
        $annee = (int) date('Y', strtotime($conge['date_debut']));
        $solde = obtenir_solde_annuel($pdo, (int) $conge['employe_id'], $annee);
        if ((int) $solde['solde_disponible'] < (int) $conge['nombre_jours']) {
            throw new RuntimeException('Solde annuel insuffisant.');
        }
        $stmt = $pdo->prepare(
            'UPDATE soldes_annuels
             SET jours_consommes = jours_consommes + ?, solde_disponible = solde_disponible - ?
             WHERE employe_id = ? AND annee = ?'
        );
        $stmt->execute([$conge['nombre_jours'], $conge['nombre_jours'], $conge['employe_id'], $annee]);
        synchroniser_ancien_solde($pdo, (int) $conge['employe_id'], (int) $solde['solde_disponible'] - (int) $conge['nombre_jours']);
    }

    $stmt = $pdo->prepare("UPDATE conges SET statut = 'validee', date_traitement = NOW() WHERE id = ? AND statut = 'en_attente_admin'");
    $stmt->execute([$idConge]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Demande déjà traitée.');
    }

    $stmt = $pdo->prepare("SELECT utilisateur_id FROM employes WHERE id = ?");
    $stmt->execute([$conge['employe_id']]);
    $utilisateur = $stmt->fetch();
    $stmt = $pdo->prepare('INSERT INTO notifications (utilisateur_id, titre, message, type_notification, lu) VALUES (?, ?, ?, ?, 0)');
    $stmt->execute([$utilisateur['utilisateur_id'], 'Congé validé', 'Votre demande de congé est définitivement validée.', 'Congé']);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

header('Location: dashboard.php?page=conges');
exit();