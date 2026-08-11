<?php

require_once "../config/auth.php";
require_once "../config/database.php";

// Récupérer l'employé connecté
$sql = "SELECT * FROM employes
        WHERE utilisateur_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['id']]);

$employe = $stmt->fetch();

$messageSucces = '';


// Traitement du formulaire
if (isset($_POST['envoyer'])) {

    $type_conge = $_POST['type_conge'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $motif = $_POST['motif'];

    // Vérification des dates
    if ($date_fin < $date_debut) {
        die("La date de fin doit être supérieure ou égale à la date de début.");
    }

    // Calcul du nombre de jours
    $debut = new DateTime($date_debut);
    $fin = new DateTime($date_fin);

    $interval = $debut->diff($fin);

    // Inclure le premier jour
    $nombre_jours = $interval->days + 1;


    // Vérifier le solde pour le congé annuel
    if ($type_conge == "Congé annuel") {

        if ($nombre_jours > $employe['solde_total']) {
            die("Votre solde de congés est insuffisant.");
        }
    }


    // Enregistrer la demande
    $sql = "INSERT INTO conges(

                employe_id,
                chef_division_id,
                type_conge,
                date_debut,
                date_fin,
                nombre_jours,
                motif,
                statut

            )

            VALUES (?, NULL, ?, ?, ?, ?, ?, 'En attente')";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        $employe['id'],
        $type_conge,
        $date_debut,
        $date_fin,
        $nombre_jours,
        $motif

    ]);


    $messageSucces = 'Votre demande de congé a été envoyée avec succès.';
}

?>
<div class="form-container">

<h1>Demande de congé</h1>

<?php if ($messageSucces !== '') { ?>
    <div class="flash-message flash-success" role="status">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= e($messageSucces); ?></span>
    </div>
<?php } ?>

<p>
    Votre solde actuel :
    <strong><?= $employe['solde_total']; ?> jours</strong>
</p>

<form method="POST">

    <label>
<i class="fa-solid fa-calendar-days"></i>
Type de congé
</label>

    <select name="type_conge" required>

    <option value="">-- Sélectionner un type de congé --</option>

    <option value="Congé annuel">
        Congé annuel
    </option>

    <option value="Congé maladie">
        Congé maladie
    </option>

    <option value="Congé exceptionnel">
        Congé exceptionnel
    </option>

</select>


    <label>
<i class="fa-solid fa-calendar"></i>
Date de début
</label>

    <input type="date"
           name="date_debut"
           required>


    <label>
<i class="fa-solid fa-calendar-check"></i>
Date de fin
</label>

    <input type="date"
           name="date_fin"
           required>


    <label>
<i class="fa-solid fa-comment"></i>
Motif
</label>

    <textarea
            name="motif"
            rows="5"
            required></textarea>


    <button type="submit" name="envoyer">

<i class="fa-solid fa-paper-plane"></i>
Envoyer la demande

</button>

</form>
</div>
