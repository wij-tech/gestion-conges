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


// Congés validés
$sql = "SELECT COUNT(*) AS total
        FROM conges
        WHERE employe_id = ?
        AND statut = 'Valide'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$employe['id']]);

$totalValide = $stmt->fetch()['total'];


// Congés en attente
$sql = "SELECT COUNT(*) AS total
        FROM conges
        WHERE employe_id = ?
        AND statut = 'En attente'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$employe['id']]);

$totalAttente = $stmt->fetch()['total'];


// Notifications non lues
$sql = "SELECT COUNT(*) AS total
        FROM notifications
        WHERE utilisateur_id = ?
        AND lu = 0";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['id']]);

$totalNotifications = $stmt->fetch()['total'];

if($_SESSION['role'] == "chef_division")
{

    // Employés de la division
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM employes
        WHERE division_id = ?
    ");
    $stmt->execute([$employe['division_id']]);
    $totalEmployesDivision = $stmt->fetch()['total'];

    // Demandes en attente
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM conges
        INNER JOIN employes
        ON conges.employe_id = employes.id
        WHERE employes.division_id = ?
        AND conges.statut='En attente'
    ");
    $stmt->execute([$employe['division_id']]);
    $totalAttenteDivision = $stmt->fetch()['total'];

    // Congés validés
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM conges
        INNER JOIN employes
        ON conges.employe_id = employes.id
        WHERE employes.division_id = ?
        AND conges.statut='Valide'
    ");
    $stmt->execute([$employe['division_id']]);
    $totalValideDivision = $stmt->fetch()['total'];

    // Congés refusés
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM conges
        INNER JOIN employes
        ON conges.employe_id = employes.id
        WHERE employes.division_id = ?
        AND conges.statut='Refuse'
    ");
    $stmt->execute([$employe['division_id']]);
    $totalRefuseDivision = $stmt->fetch()['total'];

}

// Liste des employés de la division
$sql = "SELECT
            matricule,
            nom,
            prenom,
            email,
            telephone,
            solde_total
        FROM employes
        WHERE division_id = ?
        ORDER BY nom, prenom";

$stmt = $pdo->prepare($sql);
$stmt->execute([$employe['division_id']]);

$employesDivision = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <title>Dashboard Employé</title>

    <link rel="stylesheet"
          href="../assets/css/dashboard.css?v=<?php echo time();?>">

    <link rel="stylesheet"
          href="../assets/css/table.css?v=<?php echo time();?>">

    <link rel="stylesheet"
          href="../assets/css/form.css?v=<?php echo time();?>">
          <link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div class="container">

    <div class="sidebar">

        <img src="../assets/images/logo.png"
     alt="Logo Province"
     class="sidebar-logo">

<h2 class="sidebar-title">
    Province d'Oujda
</h2>

        <ul>

<li>
    <a href="dashboard.php?page=home">
        <i class="fa-solid fa-chart-line"></i>
        Tableau de bord
    </a>
</li>

<li>
    <a href="dashboard.php?page=demander_conge">
        <i class="fa-solid fa-calendar-plus"></i>
        Demander un congé
    </a>
</li>

<li>
    <a href="dashboard.php?page=mes_conges">
        <i class="fa-solid fa-calendar-check"></i>
        Mes congés
    </a>
</li>

<li>
    <a href="dashboard.php?page=notifications">
        <i class="fa-solid fa-bell"></i>
        Mes notifications
    </a>
</li>

<?php if ($_SESSION['role'] == "chef_division") { ?>

<li>
    <a href="dashboard.php?page=demandes_division">
        <i class="fa-solid fa-file-signature"></i>
        Demandes de ma division
    </a>
</li>

<?php } ?>

<li>
    <a href="../logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        Déconnexion
    </a>
</li>

</ul>

    </div>



    <div class="main-content">

        <?php

        $page = $_GET['page'] ?? 'home';

        if ($page == 'home') {

        ?>

            <h1>Bienvenue <?= $employe['prenom']; ?> !</h1>

<div class="cards">

    <div class="card">

        <i class="fa-solid fa-wallet card-icon"></i>

        <h3>Solde restant</h3>

        <p><?= $employe['solde_total']; ?> jours</p>

    </div>

    <div class="card">

        <i class="fa-solid fa-circle-check card-icon"></i>

        <h3>Congés validés</h3>

        <p><?= $totalValide ?></p>

    </div>

    <div class="card">

        <i class="fa-solid fa-hourglass-half card-icon"></i>

        <h3>Congés en attente</h3>

        <p><?= $totalAttente ?></p>

    </div>

    <div class="card">

        <i class="fa-solid fa-bell card-icon"></i>

        <h3>Notifications</h3>

        <p><?= $totalNotifications ?></p>

    </div>

    <?php if($_SESSION['role']=="chef_division"){ ?>

<div class="cards">

    <div class="card">

        <i class="fa-solid fa-users card-icon"></i>

        <h3>Employés de la division</h3>

        <p><?= $totalEmployesDivision ?></p>

    </div>

    <div class="card">

        <i class="fa-solid fa-hourglass-half card-icon"></i>

        <h3>Demandes en attente</h3>

        <p><?= $totalAttenteDivision ?></p>

    </div>

    <div class="card">

        <i class="fa-solid fa-circle-check card-icon"></i>

        <h3>Congés validés</h3>

        <p><?= $totalValideDivision ?></p>

    </div>

    <div class="card">

        <i class="fa-solid fa-circle-xmark card-icon"></i>

        <h3>Congés refusés</h3>

        <p><?= $totalRefuseDivision ?></p>

    </div>

</div>
<?php if($_SESSION['role'] == "chef_division"){ ?>

<div class="table-container">

<h2>
<i class="fa-solid fa-users"></i>
Employés de ma division
</h2>

<table>

<tr>

    <th>Matricule</th>
    <th>Nom</th>
    <th>Prénom</th>
    <th>Email</th>
    <th>Téléphone</th>
    <th>Solde restant</th>

</tr>

<?php foreach($employesDivision as $e){ ?>

<tr>

    <td><?= $e['matricule']; ?></td>

    <td><?= $e['nom']; ?></td>

    <td><?= $e['prenom']; ?></td>

    <td><?= $e['email']; ?></td>

    <td><?= $e['telephone']; ?></td>

    <td><?= $e['solde_total']; ?> jours</td>

</tr>

<?php } ?>

</table>

</div>

<?php } ?>

<?php } ?>

</div>

        <?php

        }

        elseif ($page == 'demander_conge') {

            include 'demander_conge.php';

        }

        elseif ($page == 'mes_conges') {

            include 'mes_conges.php';

        }

        elseif ($page == 'notifications') {

            include 'notifications.php';

        }

        elseif ($page == 'demandes_division') {

            include 'demandes_division.php';

        }

        ?>

    </div>

</div>

</body>
</html>