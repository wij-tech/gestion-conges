<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/auth.php';
require_once 'config/database.php';

$stmt = $pdo->prepare(
    'SELECT utilisateurs.username, utilisateurs.password, utilisateurs.role,
            employes.nom, employes.prenom, employes.cin, employes.email,
            employes.telephone, divisions.nom_division
     FROM utilisateurs
     LEFT JOIN employes ON employes.utilisateur_id = utilisateurs.id
     LEFT JOIN divisions ON divisions.id = employes.division_id
     WHERE utilisateurs.id = ?'
);
$stmt->execute([$_SESSION['id']]);
$utilisateur = $stmt->fetch();

if (!$utilisateur) {
    http_response_code(403);
    exit('Compte introuvable.');
}

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $erreur = 'La session du formulaire a expiré. Veuillez réessayer.';
    } else {
        $ancienMotDePasse = $_POST['ancien_mot_de_passe'] ?? '';
        $nouveauMotDePasse = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirmation = $_POST['confirmation_mot_de_passe'] ?? '';

        if (!password_verify($ancienMotDePasse, $utilisateur['password'])) {
            $erreur = 'L’ancien mot de passe est incorrect.';
        } elseif (strlen($nouveauMotDePasse) < 8) {
            $erreur = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($nouveauMotDePasse !== $confirmation) {
            $erreur = 'La confirmation du mot de passe ne correspond pas.';
        } else {
            $nouveauHash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE utilisateurs SET password = ? WHERE id = ?');
            $stmt->execute([$nouveauHash, $_SESSION['id']]);
            $utilisateur['password'] = $nouveauHash;
            $message = 'Votre mot de passe a été modifié avec succès.';
        }
    }
}

$roleLabels = [
    'admin' => 'Administrateur',
    'chef_division' => 'Chef de division',
    'employe' => 'Employé'
];
$roleLabel = $roleLabels[$utilisateur['role']] ?? $utilisateur['role'];

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil</title>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= filemtime(__DIR__ . '/assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/form.css?v=<?= filemtime(__DIR__ . '/assets/css/form.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="assets/images/logo.png" alt="Logo Province" class="sidebar-logo">
            <h2 class="sidebar-title">Wilaya d'Oujda</h2>

            <ul>
                <?php if ($utilisateur['role'] === 'admin') { ?>
                <li><a href="admin/dashboard.php?page=home"><i class="fa-solid fa-chart-line"></i>Tableau de bord</a>
                </li>
                <li><a href="admin/dashboard.php?page=employes"><i class="fa-solid fa-users"></i>Gestion des
                        employés</a></li>
                <li><a href="admin/dashboard.php?page=divisions"><i class="fa-solid fa-building"></i>Gestion des
                        divisions</a></li>
                <li><a href="admin/dashboard.php?page=conges"><i class="fa-solid fa-calendar-days"></i>Gestion des
                        congés</a></li>
                <li><a href="admin/dashboard.php?page=notifications"><i class="fa-solid fa-bell"></i>Notifications</a>
                </li>
                <?php } else { ?>
                <li><a href="employe/dashboard.php?page=home"><i class="fa-solid fa-chart-line"></i>Tableau de bord</a>
                </li>
                <li><a href="employe/dashboard.php?page=demander_conge"><i
                            class="fa-solid fa-calendar-plus"></i>Demander un congé</a></li>
                <li><a href="employe/dashboard.php?page=mes_conges"><i class="fa-solid fa-calendar-check"></i>Mes
                        congés</a></li>
                <li><a href="employe/dashboard.php?page=notifications"><i class="fa-solid fa-bell"></i>Mes
                        notifications</a></li>
                <?php if ($utilisateur['role'] === 'chef_division') { ?>
                <li><a href="employe/dashboard.php?page=demandes_division"><i
                            class="fa-solid fa-file-signature"></i>Demandes de ma division</a></li>
                <?php } ?>
                <?php } ?>
            </ul>

            <div class="sidebar-footer">
                <a class="sidebar-profile" href="profil.php">
                    <div class="sidebar-avatar"><i
                            class="fa-solid <?= $utilisateur['role'] === 'admin' ? 'fa-user-shield' : 'fa-user'; ?>"></i>
                    </div>
                    <div>
                        <strong><?= e($_SESSION['username'] ?? 'Utilisateur'); ?></strong>
                        <span><?= e($roleLabel); ?></span>
                    </div>
                </a>
                <a class="sidebar-logout" href="logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Déconnexion
                </a>
            </div>
        </aside>

        <main class="main-content">
            <div class="profile-page">
                <div class="profile-header">
                    <div>
                        <p class="page-subtitle">Compte connecté</p>
                        <h1><i class="fa-solid fa-user"></i> Mon profil</h1>
                    </div>
                </div>

                <?php if ($message !== '') { ?><div class="success"><i
                        class="fa-solid fa-circle-check"></i><?= e($message); ?></div><?php } ?>
                <?php if ($erreur !== '') { ?><div class="error"><i
                        class="fa-solid fa-circle-exclamation"></i><?= e($erreur); ?></div><?php } ?>

                <section class="profile-card">
                    <h2>Informations du compte</h2>
                    <div class="profile-grid">
                        <div><span>Nom</span><strong><?= e($utilisateur['nom'] ?? 'Non renseigné'); ?></strong></div>
                        <div><span>Prénom</span><strong><?= e($utilisateur['prenom'] ?? 'Non renseigné'); ?></strong>
                        </div>
                        <div><span>CIN</span><strong><?= e($utilisateur['cin'] ?? 'Non renseigné'); ?></strong></div>
                        <div><span>Email</span><strong><?= e($utilisateur['email'] ?? 'Non renseigné'); ?></strong>
                        </div>
                        <div><span>Rôle</span><strong><?= e($roleLabel); ?></strong></div>
                        <div>
                            <span>Division</span><strong><?= e($utilisateur['nom_division'] ?? 'Non renseignée'); ?></strong>
                        </div>
                    </div>
                </section>

                <section class="profile-card">
                    <h2>Modifier mon mot de passe</h2>
                    <form method="POST" class="profile-password-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <label class="form-field"><span>Ancien mot de passe</span><input type="password"
                                name="ancien_mot_de_passe" required autocomplete="current-password"></label>
                        <label class="form-field"><span>Nouveau mot de passe</span><input type="password"
                                name="nouveau_mot_de_passe" minlength="8" required autocomplete="new-password"></label>
                        <label class="form-field"><span>Confirmer le nouveau mot de passe</span><input type="password"
                                name="confirmation_mot_de_passe" minlength="8" required
                                autocomplete="new-password"></label>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-key"></i> Modifier le mot de
                            passe</button>
                    </form>
                </section>
            </div>
        </main>
    </div>
</body>

</html>