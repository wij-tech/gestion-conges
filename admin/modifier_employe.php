<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Accès refusé.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Employé invalide.');
}


/* Employé */

$sql = "SELECT * FROM employes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$employe = $stmt->fetch();
if (!$employe) {
    http_response_code(404);
    exit('Employé introuvable.');
}


/* Divisions */

$stmtDivision = $pdo->query("SELECT * FROM divisions");
$divisions = $stmtDivision->fetchAll();

if(isset($_POST['modifier']))
{

    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Session du formulaire expirée.');
    }


    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $cin = strtoupper(trim($_POST['cin'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $date_embauche = $_POST['date_embauche'] ?? '';
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);

    if ($nom === '' || $prenom === '' || !preg_match('/^[A-Z0-9-]{4,20}$/', $cin) || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !$division_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_embauche)) {
        http_response_code(422);
        exit('Les données du formulaire sont invalides.');
    }


    $sql = "UPDATE employes

            SET

            nom = ?,
            prenom = ?,
            cin = ?,
            email = ?,
            telephone = ?,
            date_embauche = ?,
            division_id = ?

            WHERE id = ?";


    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        $nom,
        $prenom,
        $cin,
        $email,
        $telephone,
        $date_embauche,
        $division_id,
        $id

    ]);


    header("Location: dashboard.php?page=employes");
    exit();

}

?>



<div class="form-container">

<h1>
    <i class="fa-solid fa-user-pen"></i>
    Modifier un employé
</h1>

<form method="POST">

<input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">


Nom :
<br>

<input type="text"
name="nom"
value="<?= e($employe['nom']); ?>">

<br><br>


Prénom :
<br>

<input type="text"
name="prenom"
value="<?= e($employe['prenom']); ?>">

<br><br>

Numéro CIN :
<br>

<input type="text"
name="cin"
maxlength="20"
pattern="[A-Za-z0-9-]{4,20}"
value="<?= e($employe['cin']); ?>"
required>

<br><br>


Email :
<br>

<input type="email"
name="email"
value="<?= e($employe['email']); ?>">

<br><br>


Téléphone :
<br>

<input type="text"
name="telephone"
value="<?= e($employe['telephone']); ?>">

<br><br>


Date embauche :
<br>

<input type="date"
name="date_embauche"
value="<?= e($employe['date_embauche']); ?>">

<br><br>


Division :
<br>

<select name="division_id">

<?php foreach($divisions as $division){ ?>

<option
value="<?= $division['id']; ?>"

<?= ($division['id'] == $employe['division_id']) ? 'selected' : ''; ?>

>

<?= e($division['nom_division']); ?>

</option>

<?php } ?>

</select>


<br><br>

<button type="submit" name="modifier">
    <i class="fa-solid fa-floppy-disk"></i>

Modifier

</button>


</form>
</div>
