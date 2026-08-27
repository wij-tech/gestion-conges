<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Accès refusé.');
}

$erreur = '';
if (isset($_POST['ajouter'])) {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $erreur = 'La session du formulaire a expiré. Veuillez réessayer.';
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $cin = strtoupper(trim($_POST['cin'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $date_embauche = $_POST['date_embauche'] ?? '';
        $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($nom === '' || $prenom === '' || !preg_match('/^[A-Z0-9-]{4,20}$/', $cin) || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !$division_id || !in_array($role, ['employe', 'chef_division'], true)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_embauche) || strlen($password) < 8) {
            $erreur = 'Veuillez renseigner des données valides. Le CIN doit contenir 4 à 20 lettres, chiffres ou tirets.';
        } else {
            try {
                $pdo->beginTransaction();

                $baseUsername = strtolower($nom . '.' . $prenom);
                $username = $baseUsername;
                $suffixe = 2;
                $stmtUsername = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE username = ?');
                do {
                    $stmtUsername->execute([$username]);
                    if ((int) $stmtUsername->fetchColumn() === 0) {
                        break;
                    }
                    $username = $baseUsername . $suffixe++;
                } while (true);

                $stmtUser = $pdo->prepare('INSERT INTO utilisateurs(username, password, role) VALUES (?, ?, ?)');
                $stmtUser->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
                $utilisateur_id = $pdo->lastInsertId();

                $stmtEmploye = $pdo->prepare(
                    'INSERT INTO employes (nom, prenom, cin, email, telephone, date_embauche, division_id, utilisateur_id, solde_annuel, reste_annee_precedente, solde_total)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 22, 0, 22)'
                );
                $stmtEmploye->execute([$nom, $prenom, $cin, $email, $telephone, $date_embauche, $division_id, $utilisateur_id]);
                $pdo->commit();

                header('Location: dashboard.php?page=employes');
                exit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $erreur = 'Impossible d’ajouter cet employé. Vérifiez les informations fournies.';
            }
        }
    }
}

/*
 * Récupération des divisions
 */

$stmt = $pdo->query("SELECT * FROM divisions");
$divisions = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <title>Ajouter un employé</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/form.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

    <div class="main-content">

        <div class="form-container">

            <h1>
                <i class="fa-solid fa-user-plus"></i>
                Ajouter un employé
            </h1>

            <?php if ($erreur !== '') { ?>
            <p class="error"><?= e($erreur); ?></p>
            <?php } ?>

            <form method="POST">

                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

                <label> <i class="fa-solid fa-user"></i> Nom :</label>
                <input type="text" name="nom" required>


                <label> <i class="fa-solid fa-user"></i> Prénom :</label>
                <input type="text" name="prenom" required>

                <label> <i class="fa-solid fa-id-card"></i> Numéro CIN :</label>
                <input type="text" name="cin" maxlength="20" pattern="[A-Za-z0-9-]{4,20}" required>


                <label> <i class="fa-solid fa-envelope"></i> Email :</label>
                <input type="email" name="email" required>


                <label> <i class="fa-solid fa-phone"></i> Téléphone :</label>
                <input type="text" name="telephone" required>


                <label> <i class="fa-solid fa-calendar"></i> Date d'embauche :</label>
                <input type="date" name="date_embauche" required>


                <label> <i class="fa-solid fa-building"></i> Division :</label>
                <select name="division_id" required>

                    <?php foreach ($divisions as $division) { ?>

                    <option value="<?= $division['id']; ?>">
                        <?= e($division['nom_division']); ?>
                    </option>

                    <?php } ?>

                </select>


                <label> <i class="fa-solid fa-user-tie"></i> Rôle :</label>

                <select name="role" required>

                    <option value="employe">Employé</option>
                    <option value="chef_division">Chef de division</option>

                </select>


                <label> <i class="fa-solid fa-lock"></i> Mot de passe :</label>
                <input type="password" name="password" required>


                <button type="submit" name="ajouter">
                    <i class="fa-solid fa-user-plus"></i>
                    Ajouter l'employé
                </button>

            </form>

        </div>
    </div>

</body>

</html>