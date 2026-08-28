<?php

require_once "../config/auth.php";
require_once "../config/database.php";

// Récupérer l'employé connecté
$sql = "SELECT *
        FROM employes
        WHERE utilisateur_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['id']]);

$employe = $stmt->fetch();

$recherche = trim((string) ($_GET['recherche'] ?? ''));
$statut = $_GET['statut'] ?? '';
$periodeDebut = $_GET['periode_debut'] ?? '';
$periodeFin = $_GET['periode_fin'] ?? '';
$statutsAutorises = ['En attente', 'Valide', 'Refuse', 'Annule'];

if (!in_array($statut, $statutsAutorises, true)) {
    $statut = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodeDebut)) {
    $periodeDebut = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodeFin)) {
    $periodeFin = '';
}

// Récupérer tous ses congés
$conditions = ['employe_id = ?'];
$params = [$employe['id']];

if ($recherche !== '') {
    $conditions[] = '(type_conge LIKE ? OR motif LIKE ?)';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
}
if ($statut !== '') {
    $conditions[] = 'statut = ?';
    $params[] = $statut;
}
if ($periodeDebut !== '') {
    $conditions[] = 'date_fin >= ?';
    $params[] = $periodeDebut;
}
if ($periodeFin !== '') {
    $conditions[] = 'date_debut <= ?';
    $params[] = $periodeFin;
}

$sql = "SELECT *
        FROM conges
        WHERE " . implode(' AND ', $conditions) . "
        ORDER BY date_demande DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$conges = $stmt->fetchAll();

?>

<h1>Mes demandes de congé</h1>

<p>
    Votre solde actuel :
    <strong><?= $employe['solde_total']; ?> jours</strong>
</p>

<br>

<a href="dashboard.php?page=demander_conge" class="btn-ajouter">
    + Faire une nouvelle demande de congé
</a>

<br><br>

<form method="GET" class="search-form leave-filter">
    <input type="hidden" name="page" value="mes_conges">
    <input type="search" name="recherche" placeholder="Type ou motif..." value="<?= e($recherche); ?>">
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

<div class="table-container personal-leaves-table">
<div class="table-heading">
    <h2><i class="fa-solid fa-calendar-check"></i> Historique de mes demandes</h2>
    <span class="result-count"><?= count($conges); ?> demande<?= count($conges) > 1 ? 's' : ''; ?></span>
</div>

<table>

    <tr>

        <th>Type de congé</th>
        <th>Date début</th>
        <th>Date fin</th>
        <th>Nombre de jours</th>
        <th>Motif</th>
        <th>Certificat</th>
        <th>Statut</th>
        <th>Date de la demande</th>

    </tr>

    <?php foreach ($conges as $conge) { ?>

        <tr>

            <td><?= e($conge['type_conge']); ?></td>

            <td><?= e($conge['date_debut']); ?></td>

            <td><?= e($conge['date_fin']); ?></td>

            <td><?= $conge['nombre_jours']; ?></td>

            <td><?= e($conge['motif']); ?></td>

            <td>
                <?php if (!empty($conge['certificat_medical'])) { ?>
                    <a class="document-link" target="_blank" rel="noopener"
                       href="../uploads/certificats/<?= rawurlencode(basename($conge['certificat_medical'])); ?>">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </a>
                <?php } else { ?>
                    <span class="document-empty">—</span>
                <?php } ?>
            </td>

            <td>

<?php

if($conge['statut'] == "Valide")
{
    echo '<span class="badge badge-valide">
            <i class="fa-solid fa-circle-check"></i>
            Validé
          </span>';
}
elseif($conge['statut'] == "Refuse")
{
    echo '<span class="badge badge-refuse">
            <i class="fa-solid fa-circle-xmark"></i>
            Refusé
          </span>';
}
else
{
    echo '<span class="badge badge-attente">
            <i class="fa-solid fa-clock"></i>
            En attente
          </span>';
}

?>

</td>

            <td><?= e($conge['date_demande']); ?></td>

        </tr>

    <?php } ?>

    <?php if (!$conges) { ?>
        <tr class="empty-row">
            <td colspan="8"><i class="fa-solid fa-calendar-xmark"></i> Vous n’avez encore aucune demande de congé.</td>
        </tr>
    <?php } ?>

</table>
</div>
