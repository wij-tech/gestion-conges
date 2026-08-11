<?php

require_once "../config/auth.php";
require_once "../config/database.php";

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
<form method="GET" class="search-form">

    <input type="hidden" name="page" value="conges">

    <select name="division">

        <option value="">Toutes les divisions</option>

        <?php foreach($divisions as $d){ ?>

            <option value="<?= $d['id']; ?>"
                <?= ($division == $d['id']) ? "selected" : ""; ?>>

                <?= e($d['nom_division']); ?>

            </option>

        <?php } ?>

    </select>

    <select name="statut">

        <option value="">Tous les statuts</option>

        <option value="En attente"
            <?= ($statut=="En attente")?"selected":""; ?>>
            En attente
        </option>

        <option value="Valide"
            <?= ($statut=="Valide")?"selected":""; ?>>
            Validé
        </option>

        <option value="Refuse"
            <?= ($statut=="Refuse")?"selected":""; ?>>
            Refusé
        </option>

    </select>

    <select name="type">

        <option value="">Tous les types</option>

        <option value="Congé annuel"
            <?= ($type=="Congé annuel")?"selected":""; ?>>
            Congé annuel
        </option>

        <option value="Congé maladie"
            <?= ($type=="Congé maladie")?"selected":""; ?>>
            Congé maladie
        </option>

        <option value="Congé exceptionnel"
            <?= ($type=="Congé exceptionnel")?"selected":""; ?>>
            Congé exceptionnel
        </option>

    </select>

    <input type="date"
           name="date_debut"
           value="<?= e($date_debut); ?>">

    <input type="date"
           name="date_fin"
           value="<?= e($date_fin); ?>">

    <button class="btn-rechercher">

        <i class="fa-solid fa-filter"></i>

        Filtrer

    </button>

</form>

<br>

<table>

    <tr>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Division</th>
        <th>Type de congé</th>
        <th>Date début</th>
        <th>Date fin</th>
        <th>Nombre de jours</th>
        <th>Statut</th>
        <th>Date demande</th>
        <th>Date traitement</th>
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

            <td><?= e($conge['date_traitement']); ?></td>

        </tr>

    <?php } ?>

</table>
