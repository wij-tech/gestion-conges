<?php

session_start();

require_once "config/database.php";
require_once "cron/maj_solde_auto.php";

$messageErreur = '';
$username = '';

if(isset($_POST['login']))
{

    $username = trim($_POST['username'] ?? '');
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
            $messageErreur = "Mot de passe incorrect. Vérifiez vos identifiants puis réessayez.";
        }

    }
    else
    {
        $messageErreur = "Utilisateur introuvable. Vérifiez votre nom d'utilisateur.";
    }

}

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des Congés</title>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

    <div class="login-container">

        <div class="login-card">
            <img src="assets/images/logo.png" alt="Logo Province d'Oujda" class="logo">

            <h1>
                <i class="fa-solid fa-calendar-check"></i>
                Gestion des Congés
            </h1>
            <p>

                Système de gestion des congés du personnel

            </p>

            <p>Wilaya d'Oujda</p>

            <?php if ($messageErreur !== ''): ?>
            <div class="login-alert" role="alert" aria-live="polite">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <div>
                    <strong>Connexion impossible</strong>
                    <span><?= htmlspecialchars($messageErreur, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST">

                <div class="input-group <?= $messageErreur !== '' ? 'has-error' : ''; ?>">

                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    <input type="text" name="username" placeholder="Nom d'utilisateur"
                        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username"
                        aria-label="Nom d'utilisateur" required>
                </div>

                <div class="input-group <?= $messageErreur !== '' ? 'has-error' : ''; ?>">

                    <i class="fa-solid fa-lock" aria-hidden="true"></i>

                    <input type="password" name="password" placeholder="Mot de passe" autocomplete="current-password"
                        aria-label="Mot de passe" id="password" required>
                    <button type="button" class="password-toggle" aria-label="Afficher le mot de passe"
                        aria-pressed="false" title="Afficher le mot de passe">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </button>
                </div>

                <button type="submit" name="login">
                    <i class="fa-solid fa-right-to-bracket"></i>


                    Se connecter

                </button>

            </form>
            <div class="footer-login">
                © 2026 Wilaya d'Oujda-Angad

                Système de gestion des congés du personnel
            </div>

        </div>

    </div>

    <script>
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.querySelector('.password-toggle');

    passwordToggle.addEventListener('click', () => {
        const isVisible = passwordInput.type === 'text';
        passwordInput.type = isVisible ? 'password' : 'text';
        passwordToggle.setAttribute('aria-pressed', String(!isVisible));
        passwordToggle.setAttribute('aria-label', isVisible ? 'Afficher le mot de passe' :
            'Masquer le mot de passe');
        passwordToggle.setAttribute('title', isVisible ? 'Afficher le mot de passe' :
        'Masquer le mot de passe');
        passwordToggle.querySelector('i').className = isVisible ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    });
    </script>

</body>

</html>