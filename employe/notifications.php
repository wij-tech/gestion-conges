<?php

require_once "../config/auth.php";
require_once "../config/database.php";

// Récupérer les notifications de l'utilisateur connecté
$sql = "SELECT *
        FROM notifications
        WHERE utilisateur_id = ?
        ORDER BY date_notification DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['id']]);

$notifications = $stmt->fetchAll();

?>

<h1>Mes notifications</h1>

<?php if (count($notifications) == 0) { ?>

    <p>Aucune notification.</p>

<?php } else { ?>

<table>

    <tr>
        <th>Titre</th>
        <th>Message</th>
        <th>Type</th>
        <th>Statut</th>
        <th>Date</th>
        <th>Action</th>
    </tr>

    <?php foreach ($notifications as $notification) { ?>

        <tr>

            <td><?= $notification['titre']; ?></td>

            <td><?= $notification['message']; ?></td>

            <td><?= $notification['type_notification']; ?></td>

            <td>
                <?= ($notification['lu'] == 0) ? "Non lue" : "Lue"; ?>
            </td>

            <td><?= $notification['date_notification']; ?></td>

            <td>

                <?php if ($notification['lu'] == 0) { ?>

                    <a class="btn-modifier"
                       href="lire_notification.php?id=<?= $notification['id']; ?>">
                        Marquer comme lue
                    </a>

                <?php } else { ?>

                    Déjà lue

                <?php } ?>

            </td>

        </tr>

    <?php } ?>

</table>

<?php } ?>