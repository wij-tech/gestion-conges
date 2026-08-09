<?php


$id = $_GET['id'];


/* Employé */

$sql = "SELECT * FROM employes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$employe = $stmt->fetch();


/* Divisions */

$stmtDivision = $pdo->query("SELECT * FROM divisions");
$divisions = $stmtDivision->fetchAll();

if(isset($_POST['modifier']))
{

    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $date_embauche = $_POST['date_embauche'];
    $division_id = $_POST['division_id'];


    $sql = "UPDATE employes

            SET

            nom = ?,
            prenom = ?,
            email = ?,
            telephone = ?,
            date_embauche = ?,
            division_id = ?

            WHERE id = ?";


    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        $nom,
        $prenom,
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


Nom :
<br>

<input type="text"
name="nom"
value="<?= $employe['nom']; ?>">

<br><br>


Prénom :
<br>

<input type="text"
name="prenom"
value="<?= $employe['prenom']; ?>">

<br><br>


Email :
<br>

<input type="email"
name="email"
value="<?= $employe['email']; ?>">

<br><br>


Téléphone :
<br>

<input type="text"
name="telephone"
value="<?= $employe['telephone']; ?>">

<br><br>


Date embauche :
<br>

<input type="date"
name="date_embauche"
value="<?= $employe['date_embauche']; ?>">

<br><br>


Division :
<br>

<select name="division_id">

<?php foreach($divisions as $division){ ?>

<option
value="<?= $division['id']; ?>"

<?= ($division['id'] == $employe['division_id']) ? 'selected' : ''; ?>

>

<?= $division['nom_division']; ?>

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
