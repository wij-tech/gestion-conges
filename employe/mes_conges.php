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

<table>

    <tr>

        <th>Type de congé</th>
        <th>Date début</th>
        <th>Date fin</th>
        <th>Nombre de jours</th>
        <th>Motif</th>
        <th>Statut</th>
        <th>Date de la demande</th>

    </tr>

    <?php foreach ($conges as $conge) { ?>

        <tr>

            <td><?= $conge['type_conge']; ?></td>

            <td><?= $conge['date_debut']; ?></td>

            <td><?= $conge['date_fin']; ?></td>

            <td><?= $conge['nombre_jours']; ?></td>

            <td><?= $conge['motif']; ?></td>

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

            <td><?= $conge['date_demande']; ?></td>

        </tr>

    <?php } ?>

</table>