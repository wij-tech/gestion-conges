<?php

require_once "../config/auth.php";
require_once "../config/database.php";

// =====================
// Filtres
// =====================

$division = filter_input(INPUT_GET, 'division', FILTER_VALIDATE_INT) ?: '';
$statut = $_GET['statut'] ?? '';
$type = $_GET['type'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

$statutsAutorises = ['En attente', 'Valide', 'Refuse'];
$typesAutorises = ['Congé annuel', 'Congé maladie', 'Congé exceptionnel', 'Congé sans solde'];

if (!in_array($statut, $statutsAutorises, true)) {
    $statut = '';
}
if (!in_array($type, $typesAutorises, true)) {
    $type = '';
}
foreach (['date_debut', 'date_fin'] as $champ) {
    if ($$champ !== '' && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $$champ)) {
        $$champ = '';
    }
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
if ($date_debut !== '') {
    $conditionsConges[] = 'conges.date_debut >= ?';
    $paramsConges[] = $date_debut;
}
if ($date_fin !== '') {
    $conditionsConges[] = 'conges.date_fin <= ?';
    $paramsConges[] = $date_fin;
}
$whereConges = $conditionsConges ? ' WHERE ' . implode(' AND ', $conditionsConges) : '';

$sql = "SELECT
            SUM(conges.statut = 'Valide') AS valide,
            SUM(conges.statut = 'Refuse') AS refuse,
            SUM(conges.statut = 'En attente') AS attente,
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


?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
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
                    <a href="dashboard.php?page=employes">
                        <i class="fa-solid fa-users"></i>
                        Gestion des employés
                    </a>
                </li>

                <li>
                    <a href="dashboard.php?page=divisions">
                        <i class="fa-solid fa-building"></i>
                        Gestion des divisions
                    </a>
                </li>

                <li>
                    <a href="dashboard.php?page=conges">
                        <i class="fa-solid fa-calendar-days"></i>
                        Gestion des congés
                    </a>
                </li>

                <li>
                    <a href="dashboard.php?page=notifications">
                        <i class="fa-solid fa-bell"></i>
                        Notifications
                    </a>
                </li>


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

            <h1>
                <i class="fa-solid fa-chart-line"></i>
                Dashboard Administrateur
            </h1>

            <div class="cards">

                <div class="card">
                    <i class="fa-solid fa-users card-icon"></i>
                    <h3>Employés</h3>
                    <p><?= $totalEmployes ?></p>
                </div>

                <div class="card">
                    <i class="fa-solid fa-building card-icon"></i>
                    <h3>Divisions</h3>
                    <p><?= $totalDivisions ?></p>
                </div>

                <div class="card">
                    <i class="fa-solid fa-bell card-icon"></i>
                    <h3>Notifications</h3>
                    <p><?= $totalNotifications ?></p>
                </div>

                <div class="card">
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

                <div class="dashboard-section">

                    <form method="GET" class="search-form">

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
                            <option value="En attente" <?= ($statut=="En attente")?"selected":"";?>>En attente</option>
                            <option value="Valide" <?= ($statut=="Valide")?"selected":"";?>>Validé</option>
                            <option value="Refuse" <?= ($statut=="Refuse")?"selected":"";?>>Refusé</option>
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

                        <input type="date" name="date_debut" value="<?= e($date_debut); ?>">

                        <input type="date" name="date_fin" value="<?= e($date_fin); ?>">

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
                                <td><?= e($conge['statut']); ?></td>
                            </tr>
                            <?php } ?>
                        </table>
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