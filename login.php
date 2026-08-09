<?php

session_start();

require_once "config/database.php";
require_once "cron/maj_solde_auto.php";


if(isset($_POST['login']))
{

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM utilisateurs
            WHERE username = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$username]);

    $user = $stmt->fetch();


    if($user)
    {

        if(password_verify($password, $user['password']))
        {

            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];


            if($user['role'] == 'admin')
{
    header("Location: admin/dashboard.php");
    exit();
}

elseif(
    $user['role'] == 'employe'
    || $user['role'] == 'chef_division'
)
{
    header("Location: employe/dashboard.php");
    exit();
}

        }
        else
        {
            echo "Mot de passe incorrect.";
        }

    }
    else
    {
        echo "Utilisateur introuvable.";
    }

}

?>
<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestion des Congés</title>

<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div class="login-container">

    <div class="login-card">
        <img src="assets/images/logo.png"
         alt="Logo Province d'Oujda"
         class="logo">

        <h1>
<i class="fa-solid fa-calendar-check"></i>
Gestion des Congés
</h1>
<p>

Système de gestion des congés du personnel

</p>

        <p>Province d'Oujda</p>

        <form method="POST">

            <div class="input-group">

<i class="fa-solid fa-user"></i>
            <input type="text"
                   name="username"
                   placeholder="Nom d'utilisateur"
                   required>
            </div>

<div class="input-group">

<i class="fa-solid fa-lock"></i>

            <input type="password"
                   name="password"
                   placeholder="Mot de passe"
                   required>
</div>

            <button type="submit" name="login">
                <i class="fa-solid fa-right-to-bracket"></i>


                Se connecter

            </button>

        </form>
        <div class="footer-login">
    © 2026 Province d'Oujda-Angad

Système de gestion des congés du personnel
</div>

    </div>

</div>

</body>

</html>