<?php

$id = $_GET['id'];

/* Récupération de la division */

$sql = "SELECT * FROM divisions WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$division = $stmt->fetch();

/* Modification */

if(isset($_POST['modifier']))
{
    $nom_division = $_POST['nom_division'];

    $sql = "UPDATE divisions
            SET nom_division = ?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nom_division, $id]);

    header("Location: dashboard.php?page=divisions");
    exit();
}

?>

<div class="form-container">

    <h1>
        <i class="fa-solid fa-building"></i>
        Modifier la division
    </h1>

    <form method="POST">

        <div class="input-group">

            <i class="fa-solid fa-building"></i>

            <input
                type="text"
                name="nom_division"
                value="<?= $division['nom_division']; ?>"
                placeholder="Nom de la division"
                required>

        </div>

        <button type="submit" name="modifier">

            <i class="fa-solid fa-floppy-disk"></i>
            Enregistrer les modifications

        </button>

    </form>

</div>
