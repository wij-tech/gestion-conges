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


// Récupérer les demandes des employés de la division
$sql = "SELECT conges.*,
               employes.nom,
               employes.prenom
        FROM conges

        INNER JOIN employes
        ON conges.employe_id = employes.id

        WHERE employes.division_id = ?

        ORDER BY conges.date_demande DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$division_id]);

$conges = $stmt->fetchAll();

?>

<h1>Demandes de congé de ma division</h1>

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
