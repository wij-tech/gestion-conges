<?php

require_once "../config/auth.php";
require_once "../config/database.php";

// Récupérer toutes les divisions
$sql = "SELECT * FROM divisions ORDER BY id DESC";
$stmt = $pdo->query($sql);

?>

<h1>Gestion des divisions</h1>
<?php

if(isset($_GET['error']) && $_GET['error']=="employes")
{
    echo "
    <div class='alert'>
        Impossible de supprimer cette division.
        Des employés sont encore affectés à cette division.
    </div>
    ";
}

?>

<a href="dashboard.php?page=ajouter_division" class="btn-ajouter">
    + Ajouter une division
</a>

<br><br>

<table>

    <tr>
        <th>ID</th>
        <th>Nom de la division</th>
        <th>Actions</th>
    </tr>

    <?php while ($division = $stmt->fetch()) { ?>

        <tr>

            <td><?= $division['id']; ?></td>

            <td><?= e($division['nom_division']); ?></td>

            <td class="actions">

    <a class="btn-icon btn-modifier"
       href="dashboard.php?page=modifier_division&id=<?= $division['id']; ?>"
       title="Modifier">

        <i class="fa-solid fa-pen-to-square"></i>

    </a>

    <a class="btn-icon btn-supprimer"
       href="supprimer_division.php?id=<?= $division['id']; ?>"
       onclick="return confirm('Voulez-vous vraiment supprimer cette division ?')"
       title="Supprimer">

        <i class="fa-solid fa-trash"></i>

    </a>

</td>

        </tr>

    <?php } ?>

</table>
