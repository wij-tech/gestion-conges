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


// Récupérer tous ses congés
$sql = "SELECT *
        FROM conges
        WHERE employe_id = ?
        ORDER BY date_demande DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$employe['id']]);

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
