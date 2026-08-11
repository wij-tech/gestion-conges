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
$dateDebut = $_GET['date_debut'] ?? '';
$dateFin = $_GET['date_fin'] ?? '';
$rolesAutorises = ['employe', 'chef_division'];
$roleLabels = [
    'employe' => 'Employé',
    'chef_division' => 'Chef de division',
];

if (!in_array($role, $rolesAutorises, true)) {
    $role = '';
}
foreach (['dateDebut', 'dateFin'] as $champ) {
    if ($$champ !== '' && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $$champ)) {
        $$champ = '';
    }
}

$conditions = [];
$params = [];
if ($recherche !== '') {
    $conditions[] = '(employes.matricule LIKE ? OR employes.nom LIKE ? OR employes.prenom LIKE ? OR employes.cin LIKE ? OR employes.email LIKE ?)';
    $terme = '%' . $recherche . '%';
    array_push($params, $terme, $terme, $terme, $terme, $terme);
}
if ($divisionId !== '') {
    $conditions[] = 'employes.division_id = ?';
    $params[] = $divisionId;
}
if ($role !== '') {
    $conditions[] = 'utilisateurs.role = ?';
    $params[] = $role;
}
if ($dateDebut !== '') {
    $conditions[] = 'employes.date_embauche >= ?';
    $params[] = $dateDebut;
}
if ($dateFin !== '') {
    $conditions[] = 'employes.date_embauche <= ?';
    $params[] = $dateFin;
}

$sql = "SELECT employes.*, divisions.nom_division, utilisateurs.role
        FROM employes
        INNER JOIN divisions ON employes.division_id = divisions.id
        INNER JOIN utilisateurs ON employes.utilisateur_id = utilisateurs.id";
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY employes.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

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
$erreur = $messagesErreur[$_GET['error'] ?? ''] ?? '';
?>
<?php if ($erreur !== '') { ?>
    <p class="error"><?= e($erreur); ?></p>
<?php } ?>


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
           value="<?= e($recherche); ?>">

    <select name="division_id">
        <option value="">Toutes les divisions</option>
        <?php foreach ($divisionsFiltres as $division) { ?>
            <option value="<?= $division['id']; ?>"
                <?= ($divisionId == $division['id']) ? 'selected' : ''; ?>>
                <?= e($division['nom_division']); ?>
            </option>
        <?php } ?>
    </select>

    <select name="role">
        <option value="">Tous les rôles</option>
        <option value="employe" <?= $role === 'employe' ? 'selected' : ''; ?>>Employé</option>
        <option value="chef_division" <?= $role === 'chef_division' ? 'selected' : ''; ?>>Chef de division</option>
    </select>

    <input type="date" name="date_debut" value="<?= e($dateDebut); ?>" aria-label="Embauché à partir du">
    <input type="date" name="date_fin" value="<?= e($dateFin); ?>" aria-label="Embauché jusqu’au">

<button type="submit" class="btn-rechercher">
Rechercher
</button>

    <a href="dashboard.php?page=employes" class="btn-reinitialiser">Réinitialiser</a>

</form>

<br>


<!-- Tableau des employés -->

<table>

    <tr>

        <th>Matricule</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>CIN</th>
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

            <td><?= e($employe['matricule']); ?></td>

            <td><?= e($employe['nom']); ?></td>

            <td><?= e($employe['prenom']); ?></td>

            <td><?= e($employe['cin']); ?></td>

            <td><?= e($employe['email']); ?></td>

            <td><?= e($employe['telephone']); ?></td>

            <td><?= $employe['date_embauche']; ?></td>

            <td><?= e($employe['nom_division']); ?></td>

            <td><?= e($roleLabels[$employe['role']] ?? $employe['role']); ?></td>

            <td><?= $employe['solde_total']; ?> jours</td>

        <td class="actions">

    <a class="btn-icon btn-modifier"
       href="dashboard.php?page=modifier_employe&id=<?= $employe['id']; ?>"
       title="Modifier">

        <i class="fa-solid fa-pen-to-square"></i>

    </a>

    <form method="POST" action="supprimer_employe.php" class="delete-form" onsubmit="return confirm('Voulez-vous vraiment supprimer cet employé ?')">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="id" value="<?= $employe['id']; ?>">
        <button type="submit" class="btn-icon btn-supprimer" title="Supprimer" aria-label="Supprimer">
            <i class="fa-solid fa-trash"></i>
        </button>
    </form>

</td>

        </tr>

    <?php } ?>

</table>
