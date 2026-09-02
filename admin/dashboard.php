<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/statut_conge.php";
require_once "../config/graphique_conges.php";

$page = $_GET['page'] ?? 'home';
$roleLabel = 'Administrateur';

// =====================
// Filtres
// =====================

$division = filter_input(INPUT_GET, 'division', FILTER_VALIDATE_INT) ?: '';
$statut = $_GET['statut'] ?? '';
$type = $_GET['type'] ?? '';

$statutsAutorises = ['en_attente_chef', 'en_attente_admin', 'validee', 'refusee'];
$typesAutorises = ['Congé annuel', 'Congé maladie', 'Congé exceptionnel', 'Congé sans solde'];

if (!in_array($statut, $statutsAutorises, true)) {
    $statut = '';
}
if (!in_array($type, $typesAutorises, true)) {
    $type = '';
}

// Liste des divisions
$stmt = $pdo->query("SELECT * FROM divisions ORDER BY nom_division");
$divisions = $stmt->fetchAll();

// Vérifier le rôle
if ($_SESSION['role'] != "admin") {
    die("Accès refusé.");
}

// Nombre d'employés
$sql = "SELECT COUNT(*) AS total FROM employes";
$totalEmployes = $pdo->query($sql)->fetch()['total'];

// Nombre de divisions
$sql = "SELECT COUNT(*) AS total FROM divisions";
$totalDivisions = $pdo->query($sql)->fetch()['total'];

// Nombre des notifications non lues
$sql = "SELECT COUNT(*) AS total
        FROM notifications
        WHERE lu = 0";
$totalNotifications = $pdo->query($sql)->fetch()['total'];

// Conditions partagées par les statistiques et la liste des congés.
$conditionsConges = [];
$paramsConges = [];
if ($division !== '') {
    $conditionsConges[] = 'employes.division_id = ?';
    $paramsConges[] = $division;
}
if ($statut !== '') {
    $conditionsConges[] = 'conges.statut = ?';
    $paramsConges[] = $statut;
}
if ($type !== '') {
    $conditionsConges[] = 'conges.type_conge = ?';
    $paramsConges[] = $type;
}
$whereConges = $conditionsConges ? ' WHERE ' . implode(' AND ', $conditionsConges) : '';

$sql = "SELECT
            SUM(conges.statut = 'validee') AS valide,
            SUM(conges.statut = 'refusee') AS refuse,
            SUM(conges.statut IN ('en_attente_chef', 'en_attente_admin')) AS attente,
            SUM(conges.type_conge = 'Congé annuel') AS annuel,
            SUM(conges.type_conge = 'Congé maladie') AS maladie,
            SUM(conges.type_conge = 'Congé exceptionnel') AS exceptionnel,
            SUM(conges.type_conge = 'Congé sans solde') AS sans_solde
        FROM conges
        INNER JOIN employes ON conges.employe_id = employes.id"
        . $whereConges;
$stmt = $pdo->prepare($sql);
$stmt->execute($paramsConges);
$statistiquesConges = $stmt->fetch();
$totalValide = (int) $statistiquesConges['valide'];
$totalRefuse = (int) $statistiquesConges['refuse'];
$totalAttente = (int) $statistiquesConges['attente'];

// Nombre des chefs de division
$sql = "SELECT COUNT(*) AS total
        FROM utilisateurs
        WHERE role='chef_division'";
$totalChefs = $pdo->query($sql)->fetch()['total'];


$congeAnnuel = (int) $statistiquesConges['annuel'];
$congeMaladie = (int) $statistiquesConges['maladie'];
$congeExceptionnel = (int) $statistiquesConges['exceptionnel'];
$congeSansSolde = (int) $statistiquesConges['sans_solde'];


// Les 5 derniers congés

$sql = "SELECT
            employes.nom,
            employes.prenom,
            conges.type_conge,
            conges.statut

        FROM conges
        INNER JOIN employes ON conges.employe_id = employes.id"
        . $whereConges . "
        ORDER BY conges.date_demande DESC

        LIMIT 5";

$stmt = $pdo->prepare($sql);
$stmt->execute($paramsConges);
$derniersConges = $stmt->fetchAll();

$evolutionConges = compter_employes_en_conge($pdo, $division !== '' ? (int) $division : null);


?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrateur</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time();?>">

    <link rel="stylesheet" href="../assets/css/table.css?v=<?php echo time();?>">

    <link rel="stylesheet" href="../assets/css/form.css?v=<?php echo time();?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
                    <a class="<?= $page === 'employes' ? 'active' : ''; ?>" href="dashboard.php?page=employes">
                        <i class="fa-solid fa-users"></i>
                        Gestion des employés
                    </a>
                </li>

                <li>
                    <a class="<?= $page === 'divisions' ? 'active' : ''; ?>" href="dashboard.php?page=divisions">
                        <i class="fa-solid fa-building"></i>
                        Gestion des divisions
                    </a>
                </li>

                <li>
                    <a class="<?= $page === 'conges' ? 'active' : ''; ?>" href="dashboard.php?page=conges">
                        <i class="fa-solid fa-calendar-days"></i>
                        Gestion des congés
                    </a>
                </li>

                <li>
                    <a class="<?= $page === 'notifications' ? 'active' : ''; ?>"
                        href="dashboard.php?page=notifications">
                        <i class="fa-solid fa-bell"></i>
                        Notifications
                    </a>
                </li>


            </ul>

            <div class="sidebar-footer">
                <a class="sidebar-profile" href="../profil.php">
                    <div class="sidebar-avatar"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                        <strong><?= e($_SESSION['username'] ?? 'Administrateur'); ?></strong>
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

            <h1>
                <i class="fa-solid fa-chart-line"></i>
                Dashboard Administrateur
            </h1>

            <div class="cards">

                <div class="card card-primary">
                    <i class="fa-solid fa-users card-icon"></i>
                    <h3>Employés</h3>
                    <p><?= $totalEmployes ?></p>
                </div>

                <div class="card card-violet">
                    <i class="fa-solid fa-building card-icon"></i>
                    <h3>Divisions</h3>
                    <p><?= $totalDivisions ?></p>
                </div>

                <div class="card card-orange">
                    <i class="fa-solid fa-bell card-icon"></i>
                    <h3>Notifications</h3>
                    <p><?= $totalNotifications ?></p>
                </div>

                <div class="card card-green">
                    <i class="fa-solid fa-user-tie card-icon"></i>
                    <h3>Chefs de division</h3>
                    <p><?= $totalChefs ?></p>
                </div>

            </div>

            <div class="stats-summary">

                <div class="stat-box stat-box-valid">
                    <div>
                        <h3>Congés validés</h3>
                        <p><?= $totalValide ?></p>
                    </div>
                    <i class="fa-solid fa-circle-check stat-icon"></i>
                </div>

                <div class="stat-box stat-box-rejected">
                    <div>
                        <h3>Congés refusés</h3>
                        <p><?= $totalRefuse ?></p>
                    </div>
                    <i class="fa-solid fa-circle-xmark stat-icon"></i>
                </div>

                <div class="stat-box stat-box-pending">
                    <div>
                        <h3>Congés en attente</h3>
                        <p><?= $totalAttente ?></p>
                    </div>
                    <i class="fa-solid fa-hourglass-half stat-icon"></i>
                </div>

            </div>

            <div class="graphs">
                <div class="graph-container">

                    <h2>Statistiques des congés</h2>

                    <canvas id="congesChart"></canvas>

                </div>

                <div class="graph-container">

                    <h2>Types des congés</h2>

                    <canvas id="typesCongeChart"></canvas>

                </div>
                <script>
                const typesCtx = document.getElementById('typesCongeChart');


                new Chart(typesCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Annuel', 'Maladie', 'Exceptionnel', 'Sans solde'],
                        datasets: [{
                            label: 'Nombre de congés',
                            data: [
                                <?= $congeAnnuel ?>,
                                <?= $congeMaladie ?>,
                                <?= $congeExceptionnel ?>,
                                <?= $congeSansSolde ?>
                            ],
                            backgroundColor: ['#2563eb', '#22c55e', '#f59e0b', '#ef4444'],
                            borderRadius: 10,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 16
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#334155'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#334155',
                                    precision: 0
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.2)'
                                }
                            }
                        }
                    }
                });
                </script>
                <script>
                const statsCtx = document.getElementById('congesChart');

                new Chart(statsCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Validés', 'Refusés', 'En attente'],
                        datasets: [{
                            data: [
                                <?= $totalValide ?>,
                                <?= $totalRefuse ?>,
                                <?= $totalAttente ?>
                            ],
                            backgroundColor: ['#16a34a', '#dc2626', '#f59e0b'],
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#334155',
                                    boxWidth: 12,
                                    padding: 12
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed;
                                    }
                                }
                            }
                        }
                    }
                });
                </script>

                <section class="period-chart-container">
                    <div class="period-chart-header">
                        <div>
                            <h2>Employés en congé par période</h2>
                            <p>Nombre d'employés distincts ayant un congé validé pendant la période.</p>
                        </div>
                        <form method="GET" action="dashboard.php" class="period-controls">
                            <input type="hidden" name="page" value="home">
                            <input type="hidden" name="statut" value="<?= e($statut); ?>">
                            <input type="hidden" name="type" value="<?= e($type); ?>">
                            <label class="period-selector">
                                <span>Division</span>
                                <select id="adminDivisionSelect" name="division" onchange="this.form.submit()">
                                    <option value="">Toutes les divisions</option>
                                    <?php foreach ($divisions as $d) { ?>
                                    <option value="<?= (int) $d['id']; ?>" <?= $division == $d['id'] ? 'selected' : ''; ?>>
                                        <?= e($d['nom_division']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </label>
                            <label class="period-selector">
                                <span>Période</span>
                                <select id="adminPeriodSelect">
                                    <option value="semaine">Semaine</option>
                                    <option value="mois" selected>Mois</option>
                                    <option value="annee">Année</option>
                                </select>
                            </label>
                        </form>
                    </div>
                    <div class="period-chart">
                        <canvas id="adminPeriodChart"></canvas>
                    </div>
                </section>

                <script>
                (() => {
                    const chartData = <?= json_encode([
                        'semaine' => $evolutionConges['semaine'],
                        'mois' => $evolutionConges['mois'],
                        'annee' => $evolutionConges['annee']
                    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                    const select = document.getElementById('adminPeriodSelect');
                    const context = document.getElementById('adminPeriodChart');
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
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, ticks: { precision: 0 } }
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

            </div>

            <div class="dashboard-section">

                <form method="GET" class="search-form leave-filter">

                    <input type="hidden" name="page" value="home">

                    <select name="division">
                        <option value="">Toutes les divisions</option>
                        <?php foreach($divisions as $d){ ?>
                        <option value="<?= $d['id']; ?>" <?= ($division == $d['id']) ? 'selected' : ''; ?>>
                            <?= e($d['nom_division']); ?>
                        </option>
                        <?php } ?>
                    </select>

                    <select name="statut">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente_chef" <?= ($statut=="en_attente_chef")?"selected":"";?>>En attente chef</option>
                        <option value="en_attente_admin" <?= ($statut=="en_attente_admin")?"selected":"";?>>En attente admin</option>
                        <option value="validee" <?= ($statut=="validee")?"selected":"";?>>Validé</option>
                        <option value="refusee" <?= ($statut=="refusee")?"selected":"";?>>Refusé</option>
                    </select>

                    <select name="type">
                        <option value="">Tous les types</option>
                        <option value="Congé annuel" <?= ($type=="Congé annuel")?"selected":"";?>>Congé annuel
                        </option>
                        <option value="Congé maladie" <?= ($type=="Congé maladie")?"selected":"";?>>Congé maladie
                        </option>
                        <option value="Congé exceptionnel" <?= ($type=="Congé exceptionnel")?"selected":"";?>>Congé
                            exceptionnel</option>
                        <option value="Congé sans solde" <?= ($type=="Congé sans solde")?"selected":"";?>>Congé sans
                            solde</option>
                    </select>

                    <button class="btn-rechercher">
                        <i class="fa-solid fa-filter"></i> Filtrer
                    </button>

                </form>

                <div class="table-container">
                    <h2>Les 5 derniers congés demandés</h2>
                    <table>
                        <tr>
                            <th>Employé</th>
                            <th>Type de congé</th>
                            <th>Statut</th>
                        </tr>
                        <?php foreach($derniersConges as $conge){ ?>
                        <tr>
                            <td><?= e($conge['nom']); ?> <?= e($conge['prenom']); ?></td>
                            <td><?= e($conge['type_conge']); ?></td>
                            <td><?= statut_conge_badge($conge['statut']); ?></td>
                        </tr>
                        <?php } ?>
                        <?php if (!$derniersConges) { ?>
                        <tr class="empty-row">
                            <td colspan="3"><i class="fa-solid fa-inbox"></i> Aucune demande de congé à afficher.</td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>

            </div>

        </div>

        <?php

}

elseif ($page == 'employes') {

    include 'employes.php';

}

elseif ($page == 'divisions') {

    include 'divisions.php';

}

elseif ($page == 'conges') {

    include 'conges.php';

}

elseif ($page == 'notifications') {

    include 'notifications.php';

}
elseif ($page == 'ajouter_employe') {

    include 'ajouter_employe.php';

}

elseif ($page == 'modifier_employe') {

    include 'modifier_employe.php';

}
elseif ($page == 'ajouter_division') {

    include 'ajouter_division.php';

}

elseif ($page == 'modifier_division') {

    include 'modifier_division.php';

}

?>

    </div>

    </div>

</body>

</html>