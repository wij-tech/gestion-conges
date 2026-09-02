<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/solde_annuel.php";
require_once "../config/statut_conge.php";
require_once "../config/graphique_conges.php";

$page = $_GET['page'] ?? 'home';
$roleLabel = ($_SESSION['role'] ?? '') === 'chef_division' ? 'Chef de division' : 'Employé';

if (!in_array($_SESSION['role'] ?? '', ['employe', 'chef_division'], true)) {
    http_response_code(403);
    exit('Accès refusé.');
}

// Récupérer l'employé connecté
$sql = "SELECT *
        FROM employes
        WHERE utilisateur_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['id']]);

$employe = $stmt->fetch();

if (!$employe) {
    http_response_code(403);
    exit('Profil employé introuvable.');
}

$soldeAnnuel = obtenir_solde_annuel($pdo, (int) $employe['id'], (int) date('Y'));


// Congés validés
$sql = "SELECT COUNT(*) AS total
        FROM conges
        WHERE employe_id = ?
        AND statut = 'validee'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$employe['id']]);

$totalValide = $stmt->fetch()['total'];


// Congés en attente
$sql = "SELECT COUNT(*) AS total
        FROM conges
        WHERE employe_id = ?
        AND statut IN ('en_attente_chef', 'en_attente_admin')";

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
        AND conges.statut='en_attente_chef'
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
        AND conges.statut='validee'
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
        AND conges.statut='refusee'
    ");
    $stmt->execute([$employe['division_id']]);
    $totalRefuseDivision = $stmt->fetch()['total'];

}

// Liste des employés de la division
$sql = "SELECT
            cin,
            nom,
            prenom,
            email,
            telephone,
            COALESCE(soldes_annuels.solde_disponible, employes.solde_total) AS solde_actuel
        FROM employes
        LEFT JOIN soldes_annuels
            ON soldes_annuels.employe_id = employes.id
           AND soldes_annuels.annee = YEAR(CURDATE())
        WHERE division_id = ?
        ORDER BY nom, prenom";

$stmt = $pdo->prepare($sql);
$stmt->execute([$employe['division_id']]);

$employesDivision = $stmt->fetchAll();

$evolutionCongesDivision = compter_employes_en_conge($pdo, (int) $employe['division_id']);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Employé</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time();?>">

    <link rel="stylesheet" href="../assets/css/table.css?v=<?php echo time();?>">

    <link rel="stylesheet" href="../assets/css/form.css?v=<?php echo time();?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <?php if ($_SESSION['role'] === 'chef_division') { ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php } ?>

</head>

<body>

    <div class="container">

        <div class="sidebar">

            <img src="../assets/images/logo.png" alt="Logo Province" class="sidebar-logo">

            <h2 class="sidebar-title">
                Wilaya d'Oujda
            </h2>

            <ul>

                <li>
                    <a class="<?= $page === 'home' ? 'active' : ''; ?>" href="dashboard.php?page=home">
                        <i class="fa-solid fa-chart-line"></i>
                        Tableau de bord
                    </a>
                </li>

                <li>
                    <a class="<?= $page === 'demander_conge' ? 'active' : ''; ?>"
                        href="dashboard.php?page=demander_conge">
                        <i class="fa-solid fa-calendar-plus"></i>
                        Demander un congé
                    </a>
                </li>

                <li>
                    <a class="<?= $page === 'mes_conges' ? 'active' : ''; ?>" href="dashboard.php?page=mes_conges">
                        <i class="fa-solid fa-calendar-check"></i>
                        Mes congés
                    </a>
                </li>

                <li>
                    <a class="<?= $page === 'notifications' ? 'active' : ''; ?>"
                        href="dashboard.php?page=notifications">
                        <i class="fa-solid fa-bell"></i>
                        Mes notifications
                    </a>
                </li>

                <?php if ($_SESSION['role'] == "chef_division") { ?>

                <li>
                    <a class="<?= $page === 'demandes_division' ? 'active' : ''; ?>"
                        href="dashboard.php?page=demandes_division">
                        <i class="fa-solid fa-file-signature"></i>
                        Demandes de ma division
                    </a>
                </li>

                <?php } ?>

            </ul>

            <div class="sidebar-footer">
                <a class="sidebar-profile" href="../profil.php">
                    <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
                    <div>
                        <strong><?= e($_SESSION['username'] ?? 'Utilisateur'); ?></strong>
                        <span><?= e($roleLabel); ?></span>
                    </div>
                </a>

                <a class="sidebar-logout" href="../logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Déconnexion
                </a>
            </div>

        </div>



        <div class="main-content">

            <?php

        if ($page == 'home') {

        ?>

            <h1>Bienvenue <?= e($employe['prenom']); ?> !</h1>

            <div class="cards">

                <div class="card card-primary">

                    <i class="fa-solid fa-wallet card-icon"></i>

                    <h3>Solde restant</h3>

                    <p><?= $soldeAnnuel['solde_disponible']; ?> jours</p>

                </div>

                <div class="card card-green">

                    <i class="fa-solid fa-circle-check card-icon"></i>

                    <h3>Congés validés</h3>

                    <p><?= $totalValide ?></p>

                </div>

                <div class="card card-orange">

                    <i class="fa-solid fa-hourglass-half card-icon"></i>

                    <h3>Congés en attente</h3>

                    <p><?= $totalAttente ?></p>

                </div>

                <div class="card card-violet">

                    <i class="fa-solid fa-bell card-icon"></i>

                    <h3>Notifications</h3>

                    <p><?= $totalNotifications ?></p>

                </div>

            </div>

            <?php if($_SESSION['role']=="chef_division"){ ?>

            <section class="division-dashboard">
                <div class="division-heading">
                    <div>
                        <p class="eyebrow">Vue de la division</p>
                        <h2>Suivi de mon équipe</h2>
                    </div>
                    <i class="fa-solid fa-people-group"></i>
                </div>

                <div class="cards division-cards">

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

                <section class="period-chart-container">
                    <div class="period-chart-header">
                        <div>
                            <h2>Employés en congé par période</h2>
                            <p>Nombre d'employés distincts ayant un congé validé pendant la période.</p>
                        </div>
                        <label class="period-selector">
                            <span>Période</span>
                            <select id="divisionPeriodSelect">
                                <option value="semaine">Semaine</option>
                                <option value="mois" selected>Mois</option>
                                <option value="annee">Année</option>
                            </select>
                        </label>
                    </div>
                    <div class="period-chart">
                        <canvas id="divisionPeriodChart"></canvas>
                    </div>
                </section>

                <script>
                (() => {
                    const chartData = <?= json_encode([
                        'semaine' => $evolutionCongesDivision['semaine'],
                        'mois' => $evolutionCongesDivision['mois'],
                        'annee' => $evolutionCongesDivision['annee']
                    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                    const select = document.getElementById('divisionPeriodSelect');
                    const context = document.getElementById('divisionPeriodChart');
                    const periodChart = new Chart(context, {
                        type: 'line',
                        data: {
                            labels: chartData.mois.labels,
                            datasets: [{
                                label: "Nombre d'employés en congé",
                                data: chartData.mois.values,
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#2563eb'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                    select.addEventListener('change', () => {
                        periodChart.data.labels = chartData[select.value].labels;
                        periodChart.data.datasets[0].data = chartData[select.value].values;
                        periodChart.update();
                    });
                })();
                </script>

                <div class="table-container">

                    <h2>
                        <i class="fa-solid fa-users"></i>
                        Employés de ma division
                    </h2>

                    <table>

                        <tr>

                            <th>CIN</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Solde restant</th>

                        </tr>

                        <?php foreach($employesDivision as $e){ ?>

                        <tr>

                            <td><?= e($e['cin']); ?></td>

                            <td><?= e($e['nom']); ?></td>

                            <td><?= e($e['prenom']); ?></td>

                            <td><?= e($e['email']); ?></td>

                            <td><?= e($e['telephone']); ?></td>

                            <td><?= $e['solde_actuel']; ?> jours</td>

                        </tr>

                        <?php } ?>
                        <?php if (!$employesDivision) { ?>
                        <tr class="empty-row">
                            <td colspan="6"><i class="fa-solid fa-users-slash"></i> Aucun employé dans cette division.
                            </td>
                        </tr>
                        <?php } ?>

                    </table>

                </div>
            </section>

            <?php } ?>

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