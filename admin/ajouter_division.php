<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if(isset($_POST['ajouter']))
{
    $nom_division = $_POST['nom_division'];

    $sql = "INSERT INTO divisions(nom_division)
            VALUES(?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nom_division]);

    header("Location: dashboard.php?page=divisions");
    exit();
}

?>

<div class="form-container">

    <div class="form-card">

        <h2>
            <i class="fa-solid fa-building"></i>
            Ajouter une division
        </h2>

        <form method="POST">

            <label>Nom de la division</label>

            <input
                type="text"
                name="nom_division"
                placeholder="Saisir le nom de la division"
                required
            >

            <button type="submit" name="ajouter">
                <i class="fa-solid fa-plus"></i>
                Ajouter la division
            </button>

        </form>

    </div>

</div>
