<?php

require_once "../config/auth.php";
require_once "../config/database.php";

// Vérifier que l'utilisateur est un administrateur
if ($_SESSION['role'] != 'admin') {
    die("Accès refusé.");
}

// Récupérer toutes les notifications avec le nom du destinataire
$sql = "SELECT notifications.*,
               employes.nom,
               employes.prenom
        FROM notifications

        LEFT JOIN utilisateurs
        ON notifications.utilisateur_id = utilisateurs.id

        LEFT JOIN employes
        ON utilisateurs.id = employes.utilisateur_id

        ORDER BY date_notification DESC";

$stmt = $pdo->query($sql);
$notifications = $stmt->fetchAll();

?>

<h1>Toutes les notifications</h1>

<table>

    <tr>
        <th>Destinataire</th>
        <th>Titre</th>
        <th>Message</th>
        <th>Type</th>
        <th>Statut</th>
        <th>Date</th>
        <th>Action</th>
    </tr>

    <?php foreach ($notifications as $notification) { ?>

        <tr>

            <td>
                <?php
                if (!empty($notification['nom'])) {
                    echo $notification['nom'] . ' ' . $notification['prenom'];
                } else {
                    echo "Administrateur";
                }
                ?>
            </td>

            <td><?= $notification['titre']; ?></td>

            <td><?= $notification['message']; ?></td>

            <td><?= $notification['type_notification']; ?></td>

            <td>
                <?= ($notification['lu'] == 1) ? "Lue" : "Non lue"; ?>
            </td>

            <td><?= $notification['date_notification']; ?></td>
            <td class="actions">

    <a class="btn-icon btn-supprimer"
       href="supprimer_notification.php?id=<?= $notification['id']; ?>"
       onclick="return confirm('Voulez-vous supprimer cette notification ?')"
       title="Supprimer">

        <i class="fa-solid fa-trash"></i>

    </a>

</td>

        </tr>

    <?php } ?>

</table>