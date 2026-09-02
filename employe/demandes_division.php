<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/statut_conge.php";

// Vérifier le rôle
if ($_SESSION['role'] != "chef_division") {
    die("Accès refusé.");
}

// Récupérer les informations du chef connecté
$sql = "SELECT *
        FROM employes
        WHERE utilisateur_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['id']]);

$chef = $stmt->fetch();

$division_id = $chef['division_id'];

$recherche = trim((string) ($_GET['recherche'] ?? ''));
$type = $_GET['type'] ?? '';
$statut = $_GET['statut'] ?? '';
$periodeDebut = $_GET['periode_debut'] ?? '';
$periodeFin = $_GET['periode_fin'] ?? '';
$typesAutorises = ['Congé annuel', 'Congé maladie', 'Congé exceptionnel', 'Congé sans solde'];

if (!in_array($type, $typesAutorises, true)) {
    $type = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodeDebut)) {
    $periodeDebut = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodeFin)) {
    $periodeFin = '';
}

// Les demandes à traiter et l'historique sont volontairement séparés.
$conditions = ['employes.division_id = ?', 'employes.id <> ?', 'conges.statut = ?'];
$params = [$division_id, $chef['id'], 'en_attente_chef'];

if ($recherche !== '') {
    $conditions[] = '(employes.nom LIKE ? OR employes.prenom LIKE ?)';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
}
if ($type !== '') {
    $conditions[] = 'conges.type_conge = ?';
    $params[] = $type;
}
if ($periodeDebut !== '') {
    $conditions[] = 'conges.date_fin >= ?';
    $params[] = $periodeDebut;
}
if ($periodeFin !== '') {
    $conditions[] = 'conges.date_debut <= ?';
    $params[] = $periodeFin;
}

$sql = "SELECT conges.*,
               employes.nom,
               employes.prenom
        FROM conges

        INNER JOIN employes
        ON conges.employe_id = employes.id

        WHERE " . implode(' AND ', $conditions) . "

        ORDER BY conges.date_demande DESC";

$stmt = $pdo->prepare($sql);
    $stmt->execute($params);

$demandesATraiter = $stmt->fetchAll();

$historiqueConditions = ['employes.division_id = ?', 'employes.id <> ?',
    'conges.chef_division_id = ?', 'conges.statut IN (?, ?)'];
$historiqueParams = [$division_id, $chef['id'], $chef['id'], 'en_attente_admin', 'refusee'];
if ($recherche !== '') {
    $historiqueConditions[] = '(employes.nom LIKE ? OR employes.prenom LIKE ?)';
    $historiqueParams[] = '%' . $recherche . '%';
    $historiqueParams[] = '%' . $recherche . '%';
}
if ($type !== '') {
    $historiqueConditions[] = 'conges.type_conge = ?';
    $historiqueParams[] = $type;
}
if ($periodeDebut !== '') {
    $historiqueConditions[] = 'conges.date_fin >= ?';
    $historiqueParams[] = $periodeDebut;
}
if ($periodeFin !== '') {
    $historiqueConditions[] = 'conges.date_debut <= ?';
    $historiqueParams[] = $periodeFin;
}

$sqlHistorique = "SELECT conges.*, employes.nom, employes.prenom
        FROM conges
        INNER JOIN employes ON conges.employe_id = employes.id
        WHERE " . implode(' AND ', $historiqueConditions) . "
        ORDER BY conges.date_traitement DESC, conges.date_demande DESC";
$stmt = $pdo->prepare($sqlHistorique);
$stmt->execute($historiqueParams);
$historique = $stmt->fetchAll();

?>

<h1>Demandes de congé de ma division</h1>

<form method="GET" class="search-form leave-filter">
    <input type="hidden" name="page" value="demandes_division">
    <input type="search" name="recherche" placeholder="Nom ou prénom..." value="<?= e($recherche); ?>">
    <select name="type">
        <option value="">Tous les types</option>
        <?php foreach ($typesAutorises as $typeOption) { ?>
        <option value="<?= e($typeOption); ?>" <?= $type === $typeOption ? 'selected' : ''; ?>>
            <?= e($typeOption); ?>
        </option>
        <?php } ?>
    </select>
    <label class="filter-field<?= $periodeDebut !== '' ? ' has-value' : ''; ?>">
        <span>Date début</span>
        <input class="date-filter" type="date" name="periode_debut" value="<?= e($periodeDebut); ?>"
            oninput="this.parentElement.classList.toggle('has-value', this.value !== '')">
    </label>
    <label class="filter-field<?= $periodeFin !== '' ? ' has-value' : ''; ?>">
        <span>Date fin</span>
        <input class="date-filter" type="date" name="periode_fin" value="<?= e($periodeFin); ?>"
            oninput="this.parentElement.classList.toggle('has-value', this.value !== '')">
    </label>
    <button type="submit" class="btn-rechercher"><i class="fa-solid fa-filter"></i> Filtrer</button>
</form>

<section class="leave-work-section">
<div class="table-heading"><h2><i class="fa-solid fa-list-check"></i> Demandes à traiter</h2><span class="result-count"><?= count($demandesATraiter); ?></span></div>
<table class="leave-processing-table">

    <tr>

        <th>Nom</th>
        <th>Prénom</th>
        <th>Type</th>
        <th>Date début</th>
        <th>Date fin</th>
        <th>Nombre de jours</th>
        <th>Motif</th>
        <th>Certificat médical</th>
        <th>Statut</th>
        <th>Actions</th>

    </tr>

    <?php foreach ($demandesATraiter as $conge) { ?>

        <tr>

            <td><?= e($conge['nom']); ?></td>

            <td><?= e($conge['prenom']); ?></td>

            <td><?= e($conge['type_conge']); ?></td>

            <td><?= $conge['date_debut']; ?></td>

            <td><?= $conge['date_fin']; ?></td>

            <td><?= $conge['nombre_jours']; ?></td>

            <td><?= e($conge['motif']); ?></td>

            <td>
                <?php if (!empty($conge['certificat_medical'])) { ?>
                    <a class="btn-modifier btn-sm" target="_blank" rel="noopener"
                       href="../uploads/certificats/<?= rawurlencode(basename($conge['certificat_medical'])); ?>">
                        <i class="fa-solid fa-file-pdf"></i> Voir le PDF
                    </a>
                <?php } else { ?>
                    —
                <?php } ?>
            </td>

            <td><?= statut_conge_badge($conge['statut']); ?></td>

            <td>

                <?php if ($conge['statut'] === 'en_attente_chef') { ?>

                    <div class="actions">

                        <a class="btn-modifier"
                           href="valider_conge.php?id=<?= $conge['id']; ?>">
                            Valider
                        </a>

                        <a class="btn-supprimer"
                           href="refuser_conge.php?id=<?= $conge['id']; ?>">
                            Refuser
                        </a>

                    </div>

                <?php } else { ?>

                    Traitée

                <?php } ?>

            </td>

        </tr>

    <?php } ?>

    <?php if (!$demandesATraiter) { ?>
        <tr class="empty-row">
            <td colspan="10"><i class="fa-solid fa-inbox"></i> Aucune demande de congé dans votre division.</td>
        </tr>
    <?php } ?>

</table>
</section>

<section class="leave-work-section leave-history-section">
    <div class="table-heading">
        <h2><i class="fa-solid fa-clock-rotate-left"></i> Historique des demandes traitées</h2>
        <span class="result-count"><?= count($historique); ?></span>
    </div>
    <table class="leave-processing-table">
        <tr><th>Nom</th><th>Prénom</th><th>Type</th><th>Date début</th><th>Date fin</th><th>Statut</th><th>Date traitement</th></tr>
        <?php foreach ($historique as $conge) { ?>
        <tr>
            <td><?= e($conge['nom']); ?></td>
            <td><?= e($conge['prenom']); ?></td>
            <td><?= e($conge['type_conge']); ?></td>
            <td><?= e($conge['date_debut']); ?></td>
            <td><?= e($conge['date_fin']); ?></td>
            <td><?= statut_conge_badge($conge['statut']); ?></td>
            <td><?= e($conge['date_traitement']); ?></td>
        </tr>
        <?php } ?>
        <?php if (!$historique) { ?><tr class="empty-row"><td colspan="7">Aucune demande traitée par vous.</td></tr><?php } ?>
    </table>
</section>
