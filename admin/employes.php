<?php

require_once "../config/auth.php";
require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Recherche d'un employé
|--------------------------------------------------------------------------
*/

if (isset($_GET['recherche']) && !empty($_GET['recherche'])) {

    $recherche = "%" . $_GET['recherche'] . "%";

    $sql = "SELECT
                employes.*,
                divisions.nom_division,
                utilisateurs.role

            FROM employes

            INNER JOIN divisions
            ON employes.division_id = divisions.id

            INNER JOIN utilisateurs
            ON employes.utilisateur_id = utilisateurs.id

            WHERE

                employes.matricule LIKE ?
                OR employes.nom LIKE ?
                OR employes.prenom LIKE ?
                OR employes.email LIKE ?

            ORDER BY employes.id DESC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        $recherche,
        $recherche,
        $recherche,
        $recherche

    ]);

} else {

    $sql = "SELECT
                employes.*,
                divisions.nom_division,
                utilisateurs.role

            FROM employes

            INNER JOIN divisions
            ON employes.division_id = divisions.id

            INNER JOIN utilisateurs
            ON employes.utilisateur_id = utilisateurs.id

            ORDER BY employes.id DESC";

    $stmt = $pdo->query($sql);
}

?>

<h1>Gestion des employés</h1>


<!-- Bouton ajouter -->

<a href="dashboard.php?page=ajouter_employe" class="btn-ajouter">
    + Ajouter un employé
</a>

<br><br>


<!-- Barre de recherche -->

<form method="GET" action="dashboard.php" class="search-form">

    <input type="hidden" name="page" value="employes">

    <input type="text"
           name="recherche"
           placeholder="Rechercher un employé..."
           value="<?= $_GET['recherche'] ?? ''; ?>">

    <button type="submit" class="btn-rechercher">
    Rechercher
</button>

</form>

<br>


<!-- Tableau des employés -->

<table>

    <tr>

        <th>Matricule</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Email</th>
        <th>Téléphone</th>
        <th>Date embauche</th>
        <th>Division</th>
        <th>Rôle</th>
        <th>Solde</th>
        <th>Actions</th>

    </tr>

    <?php while ($employe = $stmt->fetch()) { ?>

        <tr>

            <td><?= $employe['matricule']; ?></td>

            <td><?= $employe['nom']; ?></td>

            <td><?= $employe['prenom']; ?></td>

            <td><?= $employe['email']; ?></td>

            <td><?= $employe['telephone']; ?></td>

            <td><?= $employe['date_embauche']; ?></td>

            <td><?= $employe['nom_division']; ?></td>

            <td><?= $employe['role']; ?></td>

            <td><?= $employe['solde_total']; ?> jours</td>

        <td class="actions">

    <a class="btn-icon btn-modifier"
       href="dashboard.php?page=modifier_employe&id=<?= $employe['id']; ?>"
       title="Modifier">

        <i class="fa-solid fa-pen-to-square"></i>

    </a>

    <a class="btn-icon btn-supprimer"
       href="supprimer_employe.php?id=<?= $employe['id']; ?>"
       onclick="return confirm('Voulez-vous vraiment supprimer cet employé ?')"
       title="Supprimer">

        <i class="fa-solid fa-trash"></i>

    </a>

</td>

        </tr>

    <?php } ?>

</table>