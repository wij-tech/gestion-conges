<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/statut_conge.php";

// =======================
// Filtres
// =======================

$division = $_GET['division'] ?? '';
$statut = $_GET['statut'] ?? '';
$type = $_GET['type'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

// Liste des divisions
$stmtDiv = $pdo->query("SELECT * FROM divisions ORDER BY nom_division");
$divisions = $stmtDiv->fetchAll();

$sql = "SELECT
            conges.*,
            employes.nom,
            employes.prenom,
            divisions.nom_division

        FROM conges

        INNER JOIN employes
        ON conges.employe_id = employes.id

        INNER JOIN divisions
        ON employes.division_id = divisions.id

        WHERE 1=1";

$params = [];

if($division != "")
{
    $sql .= " AND divisions.id = ?";
    $params[] = $division;
}

if($statut != "")
{
    $sql .= " AND conges.statut = ?";
    $params[] = $statut;
}

if($type != "")
{
    $sql .= " AND conges.type_conge = ?";
    $params[] = $type;
}

if($date_debut != "")
{
    $sql .= " AND conges.date_debut >= ?";
    $params[] = $date_debut;
}

if($date_fin != "")
{
    $sql .= " AND conges.date_fin <= ?";
    $params[] = $date_fin;
}

$sql .= " ORDER BY conges.date_demande DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$conges = $stmt->fetchAll();

?>

<h1>Gestion des congés</h1>
<form method="GET" class="search-form leave-filter">

    <input type="hidden" name="page" value="conges">

    <select name="division">

        <option value="">Toutes les divisions</option>

        <?php foreach($divisions as $d){ ?>

        <option value="<?= $d['id']; ?>" <?= ($division == $d['id']) ? "selected" : ""; ?>>

            <?= e($d['nom_division']); ?>

        </option>

        <?php } ?>

    </select>

    <select name="statut">

        <option value="">Tous les statuts</option>

        <option value="en_attente_chef" <?= ($statut=="en_attente_chef")?"selected":""; ?>>
            En attente chef
        </option>

        <option value="en_attente_admin" <?= ($statut=="en_attente_admin")?"selected":""; ?>>
            En attente admin
        </option>

        <option value="validee" <?= ($statut=="validee")?"selected":""; ?>>
            Validé
        </option>

        <option value="refusee" <?= ($statut=="refusee")?"selected":""; ?>>
            Refusé
        </option>

    </select>

    <select name="type">

        <option value="">Tous les types</option>

        <option value="Congé annuel" <?= ($type=="Congé annuel")?"selected":""; ?>>
            Congé annuel
        </option>

        <option value="Congé maladie" <?= ($type=="Congé maladie")?"selected":""; ?>>
            Congé maladie
        </option>

        <option value="Congé exceptionnel" <?= ($type=="Congé exceptionnel")?"selected":""; ?>>
            Congé exceptionnel
        </option>

    </select>

    <div class="filter-date <?= $date_debut !== '' ? 'has-value' : ''; ?>">
        <i class="fa-solid fa-calendar"></i>
        <input type="date" name="date_debut" value="<?= e($date_debut); ?>" aria-label="Date de début"
            oninput="this.parentElement.classList.toggle('has-value', this.value !== '')">
        <span>Date de début</span>
    </div>

    <div class="filter-date <?= $date_fin !== '' ? 'has-value' : ''; ?>">
        <i class="fa-solid fa-calendar-check"></i>
        <input type="date" name="date_fin" value="<?= e($date_fin); ?>" aria-label="Date de fin"
            oninput="this.parentElement.classList.toggle('has-value', this.value !== '')">
        <span>Date de fin</span>
    </div>

    <button class="btn-rechercher">

        <i class="fa-solid fa-filter"></i>

        Filtrer

    </button>

    <a href="dashboard.php?page=conges" class="btn-reinitialiser">
        <i class="fa-solid fa-rotate-left"></i>
        Réinitialiser
    </a>

</form>

<div class="table-container leave-table-container">
    <div class="table-heading">
        <h2><i class="fa-solid fa-calendar-days"></i> Liste des demandes de congé</h2>
        <span class="result-count"><?= count($conges); ?> résultat<?= count($conges) > 1 ? 's' : ''; ?></span>
    </div>

    <table>

        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Division</th>
            <th>Type de congé</th>
            <th>Date début</th>
            <th>Date fin</th>
            <th>Nombre de jours</th>
            <th>Justificatif</th>
            <th>Statut</th>
            <th>Date demande</th>
            <th>Date traitement</th>
            <th>Actions</th>
        </tr>

        <?php foreach($conges as $conge){ ?>

        <tr>

            <td><?= e($conge['nom']); ?></td>

            <td><?= e($conge['prenom']); ?></td>

            <td><?= e($conge['nom_division']); ?></td>

            <td><?= e($conge['type_conge']); ?></td>

            <td><?= e($conge['date_debut']); ?></td>

            <td><?= e($conge['date_fin']); ?></td>

            <td><?= $conge['nombre_jours']; ?></td>

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

                                <?= statut_conge_badge($conge['statut']); ?>

            </td>

            <td><?= e($conge['date_demande']); ?></td>

            <td><?= e($conge['date_traitement']); ?></td>

            <td class="actions">
                <?php if ($conge['statut'] === 'en_attente_admin') { ?>
                <form method="POST" action="valider_conge.php" class="delete-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                    <input type="hidden" name="id" value="<?= (int) $conge['id']; ?>">
                    <button type="submit" class="btn-modifier btn-sm">Valider</button>
                </form>
                <form method="POST" action="refuser_conge.php" class="delete-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                    <input type="hidden" name="id" value="<?= (int) $conge['id']; ?>">
                    <button type="submit" class="btn-supprimer btn-sm">Refuser</button>
                </form>
                <?php } else { ?>
                Traitée
                <?php } ?>
            </td>

        </tr>

        <?php } ?>

        <?php if (!$conges) { ?>
        <tr class="empty-row">
            <td colspan="12"><i class="fa-solid fa-inbox"></i> Aucune demande de congé ne correspond aux filtres
                sélectionnés.</td>
        </tr>
        <?php } ?>

    </table>
</div>