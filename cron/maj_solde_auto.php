<?php

require_once __DIR__ . "/../config/database.php";

$anneeActuelle = date("Y");

/* Vérifier la dernière mise à jour */

$sql = "SELECT derniere_maj_solde FROM parametres LIMIT 1";
$stmt = $pdo->query($sql);
$parametre = $stmt->fetch();

/* Si la mise à jour est déjà faite */

if($parametre['derniere_maj_solde'] == $anneeActuelle){
    return;
}

/* Mise à jour des soldes */

$sql = "SELECT id, utilisateur_id, solde_total
        FROM employes";

$stmt = $pdo->query($sql);
$employes = $stmt->fetchAll();

foreach($employes as $employe){

    $ancienSolde = $employe['solde_total'];
    $nouveauSolde = $ancienSolde + 22;

    $stmtUpdate = $pdo->prepare("
        UPDATE employes
        SET
        reste_annee_precedente = ?,
        solde_total = ?
        WHERE id = ?
    ");

    $stmtUpdate->execute([
        $ancienSolde,
        $nouveauSolde,
        $employe['id']
    ]);
}

/* Enregistrer l'année de mise à jour */

$stmt = $pdo->prepare("
UPDATE parametres
SET derniere_maj_solde = ?
");

$stmt->execute([$anneeActuelle]);

?>