<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/solde_annuel.php";

$anneeActuelle = (int) date("Y");

garantir_table_soldes_annuels($pdo);

/* Vérifier la dernière mise à jour */

$sql = "SELECT derniere_maj_solde FROM parametres LIMIT 1";
$stmt = $pdo->query($sql);
$parametre = $stmt->fetch();

/* Si la mise à jour est déjà faite */

if($parametre && (int) $parametre['derniere_maj_solde'] == $anneeActuelle){
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM soldes_annuels WHERE annee = ?');
    $stmt->execute([$anneeActuelle]);
    if ((int) $stmt->fetchColumn() > 0) {
        return;
    }
}

/* Mise à jour des soldes */

$sql = "SELECT id
        FROM employes";

$stmt = $pdo->query($sql);
$employes = $stmt->fetchAll();

foreach($employes as $employe){
    $solde = obtenir_solde_annuel($pdo, (int) $employe['id'], $anneeActuelle);
    synchroniser_ancien_solde($pdo, (int) $employe['id'], (int) $solde['solde_disponible']);
}

/* Enregistrer l'année de mise à jour */

$stmt = $pdo->prepare("INSERT INTO parametres (id, derniere_maj_solde)
                       VALUES (1, ?)
                       ON DUPLICATE KEY UPDATE derniere_maj_solde = VALUES(derniere_maj_solde)");

$stmt->execute([$anneeActuelle]);

?>