<?php

require_once "../config/auth.php";
require_once "../config/database.php";

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
$statutsAutorises = ['En attente', 'Valide', 'Refuse', 'Annule'];

if (!in_array($type, $typesAutorises, true)) {
    $type = '';
}
if (!in_array($statut, $statutsAutorises, true)) {
    $statut = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodeDebut)) {
    $periodeDebut = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodeFin)) {
    $periodeFin = '';
}

// Récupérer les demandes des employés de la division
$conditions = ['employes.division_id = ?'];
$params = [$division_id];

if ($recherche !== '') {
    $conditions[] = '(employes.nom LIKE ? OR employes.prenom LIKE ?)';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
}
if ($type !== '') {
    $conditions[] = 'conges.type_conge = ?';
    $params[] = $type;
}
if ($statut !== '') {
    $conditions[] = 'conges.statut = ?';
    $params[] = $statut;
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

$conges = $stmt->fetchAll();

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
    <select name="statut">
        <option value="">Tous les statuts</option>
        <?php foreach ($statutsAutorises as $statutOption) { ?>
        <option value="<?= e($statutOption); ?>" <?= $statut === $statutOption ? 'selected' : ''; ?>>
            <?= e($statutOption); ?>
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

<table>

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

    <?php foreach ($conges as $conge) { ?>

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

            <td><?= e($conge['statut']); ?></td>

            <td>

                <?php if ($conge['statut'] == "En attente") { ?>

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

    <?php if (!$conges) { ?>
        <tr class="empty-row">
            <td colspan="10"><i class="fa-solid fa-inbox"></i> Aucune demande de congé dans votre division.</td>
        </tr>
    <?php } ?>

</table>
