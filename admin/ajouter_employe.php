<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if (isset($_POST['ajouter'])) {

    // Récupération des données
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $date_embauche = $_POST['date_embauche'];
    $division_id = $_POST['division_id'];
    $role = $_POST['role'];
    $password = $_POST['password'];

    // Générer le nom d'utilisateur
    $username = strtolower($nom . "." . $prenom);

    // Générer le matricule
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM employes");
    $result = $stmt->fetch();

    $numero = $result['total'] + 1;

    $matricule = "EMP" . str_pad($numero, 3, "0", STR_PAD_LEFT);

    // Hasher le mot de passe
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Ajouter l'utilisateur
    $sqlUser = "INSERT INTO utilisateurs(username, password, role)
                VALUES (?, ?, ?)";

    $stmtUser = $pdo->prepare($sqlUser);

    $stmtUser->execute([
        $username,
        $password_hash,
        $role
    ]);

    // Récupérer l'id du compte créé
    $utilisateur_id = $pdo->lastInsertId();

    // Ajouter l'employé
    $sqlEmploye = "INSERT INTO employes(

        matricule,
        nom,
        prenom,
        email,
        telephone,
        date_embauche,
        division_id,
        utilisateur_id,
        solde_annuel,
        reste_annee_precedente,
        solde_total

    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 22, 0, 22)";

    $stmtEmploye = $pdo->prepare($sqlEmploye);

    $stmtEmploye->execute([

        $matricule,
        $nom,
        $prenom,
        $email,
        $telephone,
        $date_embauche,
        $division_id,
        $utilisateur_id

    ]);

    header("Location: dashboard.php?page=employes");
exit();
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
    <link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div class="main-content">

<div class="form-container">

<h1>
    <i class="fa-solid fa-user-plus"></i>
    Ajouter un employé
</h1>

<form method="POST">

        <label> <i class="fa-solid fa-user"></i> Nom :</label>
        <input type="text" name="nom" required>


        <label> <i class="fa-solid fa-user"></i> Prénom :</label>
        <input type="text" name="prenom" required>


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
                    <?= $division['nom_division']; ?>
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