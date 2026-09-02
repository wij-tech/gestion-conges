<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Accès refusé.');
}

/*
|--------------------------------------------------------------------------
| Recherche d'un employé
|--------------------------------------------------------------------------
*/

$recherche = trim($_GET['recherche'] ?? '');
$divisionId = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT) ?: '';
$role = $_GET['role'] ?? '';
$dateEmbauche = $_GET['date_embauche'] ?? '';
$statut = $_GET['statut'] ?? 'actif';
$rolesAutorises = ['employe', 'chef_division'];
$roleLabels = [
    'employe' => 'Employé',
    'chef_division' => 'Chef de division',
];

if (!in_array($role, $rolesAutorises, true)) {
    $role = '';
}
if ($dateEmbauche !== '' && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $dateEmbauche)) {
    $dateEmbauche = '';
}
if (!in_array($statut, ['actif', 'inactif', 'tous'], true)) {
    $statut = 'actif';
}

$conditions = [];
$params = [];
if ($recherche !== '') {
    $conditions[] = '(employes.nom LIKE ? OR employes.prenom LIKE ? OR employes.cin LIKE ? OR employes.email LIKE ?)';
    $terme = '%' . $recherche . '%';
    array_push($params, $terme, $terme, $terme, $terme);
}
if ($divisionId !== '') {
    $conditions[] = 'employes.division_id = ?';
    $params[] = $divisionId;
}
if ($role !== '') {
    $conditions[] = 'utilisateurs.role = ?';
    $params[] = $role;
}
if ($dateEmbauche !== '') {
    $conditions[] = 'employes.date_embauche = ?';
    $params[] = $dateEmbauche;
}
if ($statut !== 'tous') {
    $conditions[] = 'utilisateurs.statut = ?';
    $params[] = $statut;
}

$sql = "SELECT employes.*, divisions.nom_division, utilisateurs.role, utilisateurs.statut,
           COALESCE(soldes_annuels.solde_disponible, employes.solde_total) AS solde_actuel
        FROM employes
        INNER JOIN divisions ON employes.division_id = divisions.id
    INNER JOIN utilisateurs ON employes.utilisateur_id = utilisateurs.id
    LEFT JOIN soldes_annuels
        ON soldes_annuels.employe_id = employes.id
       AND soldes_annuels.annee = YEAR(CURDATE())";
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY employes.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employes = $stmt->fetchAll();

$stmtDivisions = $pdo->query('SELECT id, nom_division FROM divisions ORDER BY nom_division');
$divisionsFiltres = $stmtDivisions->fetchAll();

?>

<h1>Gestion des employés</h1>

<?php
$messagesErreur = [
    'conges' => 'Cet employé ne peut pas être supprimé car il possède des demandes de congé.',
    'introuvable' => 'Employé introuvable.',
    'invalide' => 'Employé invalide.',
    'suppression' => 'La suppression a échoué. Aucune donnée n’a été supprimée.',
];
$messagesStatut = [
    'desactive' => 'Employé désactivé. Son historique a été conservé.',
    'reactive' => 'Employé réactivé avec succès.',
];
$erreur = $messagesErreur[$_GET['error'] ?? ''] ?? '';
$messageStatut = $messagesStatut[$_GET['status'] ?? ''] ?? '';
?>
<?php if ($erreur !== '') { ?>
<p class="error"><?= e($erreur); ?></p>
<?php } ?>
<?php if ($messageStatut !== '') { ?>
<p class="success"><?= e($messageStatut); ?></p>
<?php } ?>


<!-- Bouton ajouter -->

<a href="dashboard.php?page=ajouter_employe" class="btn-ajouter">
    + Ajouter un employé
</a>

<br><br>


<!-- Barre de recherche -->

<form method="GET" action="dashboard.php" class="search-form employee-filter">

    <input type="hidden" name="page" value="employes">

    <input type="text" name="recherche" placeholder="Rechercher un employé..." value="<?= e($recherche); ?>">

    <select name="division_id">
        <option value="">Toutes les divisions</option>
        <?php foreach ($divisionsFiltres as $division) { ?>
        <option value="<?= $division['id']; ?>" <?= ($divisionId == $division['id']) ? 'selected' : ''; ?>>
            <?= e($division['nom_division']); ?>
        </option>
        <?php } ?>
    </select>

    <select name="role">
        <option value="">Tous les rôles</option>
        <option value="employe" <?= $role === 'employe' ? 'selected' : ''; ?>>Employé</option>
        <option value="chef_division" <?= $role === 'chef_division' ? 'selected' : ''; ?>>Chef de division</option>
    </select>

    <select name="statut">
        <option value="actif" <?= $statut === 'actif' ? 'selected' : ''; ?>>Employés actifs</option>
        <option value="inactif" <?= $statut === 'inactif' ? 'selected' : ''; ?>>Employés inactifs</option>
        <option value="tous" <?= $statut === 'tous' ? 'selected' : ''; ?>>Tous les statuts</option>
    </select>

    <div class="filter-date <?= $dateEmbauche !== '' ? 'has-value' : ''; ?>">
        <i class="fa-solid fa-calendar"></i>
        <input id="date_embauche" type="date" name="date_embauche" value="<?= e($dateEmbauche); ?>"
            aria-label="Date d’embauche" oninput="this.parentElement.classList.toggle('has-value', this.value !== '')">
        <span>Date d’embauche</span>
    </div>

    <button type="submit" class="btn-rechercher">
        Rechercher
    </button>

    <a href="dashboard.php?page=employes" class="btn-reinitialiser">Réinitialiser</a>

</form>

<br>


<!-- Tableau des employés -->

<table>

    <tr>

        <th>CIN</th>
        <th>Nom</th>
        <th>Prénom</th>

        <th>Email</th>
        <th>Téléphone</th>
        <th>Date embauche</th>
        <th>Division</th>
        <th>Rôle</th>
        <th>Solde</th>
        <th>Statut</th>
        <th>Actions</th>

    </tr>

    <?php foreach ($employes as $employe) { ?>

    <tr>

        <td><?= e($employe['cin']); ?></td>

        <td><?= e($employe['nom']); ?></td>

        <td><?= e($employe['prenom']); ?></td>



        <td><?= e($employe['email']); ?></td>

        <td><?= e($employe['telephone']); ?></td>

        <td><?= $employe['date_embauche']; ?></td>

        <td><?= e($employe['nom_division']); ?></td>

        <td><?= e($roleLabels[$employe['role']] ?? $employe['role']); ?></td>

        <td><?= $employe['solde_actuel']; ?> jours</td>

        <td><?= e(ucfirst($employe['statut'])); ?></td>

        <td class="actions">

            <a class="btn-icon btn-modifier" href="dashboard.php?page=modifier_employe&id=<?= $employe['id']; ?>"
                title="Modifier">

                <i class="fa-solid fa-pen-to-square"></i>

            </a>

            <?php if ($employe['statut'] === 'actif') { ?>
            <form method="POST" action="supprimer_employe.php" class="delete-form"
                onsubmit="return confirm('Voulez-vous désactiver cet employé ?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?= $employe['id']; ?>">
                <button type="submit" class="btn-icon btn-supprimer" title="Supprimer" aria-label="Supprimer">
                    <i class="fa-solid fa-user-slash"></i>
                </button>
            </form>
            <?php } else { ?>
            <form method="POST" action="reactiver_employe.php" class="delete-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?= $employe['id']; ?>">
                <button type="submit" class="btn-icon btn-modifier" title="Réactiver" aria-label="Réactiver">
                    <i class="fa-solid fa-user-check"></i>
                </button>
            </form>
            <?php } ?>

        </td>

    </tr>

    <?php } ?>

    <?php if (!$employes) { ?>
    <tr class="empty-row">
        <td colspan="11"><i class="fa-solid fa-users-slash"></i> Aucun employé ne correspond à votre recherche.</td>
    </tr>
    <?php } ?>

</table>