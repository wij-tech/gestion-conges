<?php

require_once "../config/auth.php";
require_once "../config/database.php";

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
            cin,
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

$anneeCourante = (int) date('Y');
$stmt = $pdo->prepare("SELECT MONTH(conges.date_debut) AS periode, COUNT(*) AS total
                       FROM conges
                       INNER JOIN employes ON employes.id = conges.employe_id
                       WHERE employes.division_id = ? AND YEAR(conges.date_debut) = ?
                       GROUP BY MONTH(conges.date_debut)
                       ORDER BY periode");
$stmt->execute([$employe['division_id'], $anneeCourante]);
$evolutionMoisDivision = array_fill(1, 12, 0);
foreach ($stmt->fetchAll() as $ligne) {
    $evolutionMoisDivision[(int) $ligne['periode']] = (int) $ligne['total'];
}

$stmt = $pdo->prepare("SELECT WEEK(conges.date_debut, 1) AS periode, COUNT(*) AS total
                       FROM conges
                       INNER JOIN employes ON employes.id = conges.employe_id
                       WHERE employes.division_id = ? AND YEAR(conges.date_debut) = ?
                       GROUP BY WEEK(conges.date_debut, 1)
                       ORDER BY periode");
$stmt->execute([$employe['division_id'], $anneeCourante]);
$evolutionSemaineDivision = array_fill(1, 53, 0);
foreach ($stmt->fetchAll() as $ligne) {
    $semaine = (int) $ligne['periode'];
    if ($semaine >= 1 && $semaine <= 53) {
        $evolutionSemaineDivision[$semaine] = (int) $ligne['total'];
    }
}

$stmt = $pdo->prepare("SELECT YEAR(conges.date_debut) AS periode, COUNT(*) AS total
                       FROM conges
                       INNER JOIN employes ON employes.id = conges.employe_id
                       WHERE employes.division_id = ?
                       GROUP BY YEAR(conges.date_debut)
                       ORDER BY periode");
$stmt->execute([$employe['division_id']]);
$evolutionAnneeDivision = [];
foreach ($stmt->fetchAll() as $ligne) {
    $evolutionAnneeDivision[(string) $ligne['periode']] = (int) $ligne['total'];
}

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
                <div class="sidebar-profile">
                    <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
                    <div>
                        <strong><?= e($_SESSION['username'] ?? 'Utilisateur'); ?></strong>
                        <span><?= e($roleLabel); ?></span>
                    </div>
                </div>

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

                    <p><?= $employe['solde_total']; ?> jours</p>

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
                            <h2>Évolution des congés</h2>
                            <p>Suivez l'évolution des demandes de votre division.</p>
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
                        'semaine' => ['labels' => array_map(fn($value) => 'S' . $value, array_keys($evolutionSemaineDivision)), 'values' => array_values($evolutionSemaineDivision)],
                        'mois' => ['labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'], 'values' => array_values($evolutionMoisDivision)],
                        'annee' => ['labels' => array_keys($evolutionAnneeDivision), 'values' => array_values($evolutionAnneeDivision)]
                    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                    const select = document.getElementById('divisionPeriodSelect');
                    const context = document.getElementById('divisionPeriodChart');
                    const periodChart = new Chart(context, {
                        type: 'line',
                        data: {
                            labels: chartData.mois.labels,
                            datasets: [{
                                label: 'Nombre de congés',
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

                            <td><?= $e['solde_total']; ?> jours</td>

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