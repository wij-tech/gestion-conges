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

<div class="notification-panel">
    <table class="notification-table">

        <tr>
            <th>Titre</th>
            <th>Message</th>
            <th>Type</th>
            <th>Statut</th>
            <th>Date</th>
        </tr>

        <?php foreach ($notifications as $notification) { ?>

        <tr class="<?= $notification['lu'] == 0 ? 'notification-unread' : ''; ?>">

            <td><?= e($notification['titre']); ?></td>

            <td><?= e($notification['message']); ?></td>

            <td><?= e($notification['type_notification']); ?></td>

            <td>
                <?php if ($notification['lu'] == 0) { ?>

                <a class="notification-status notification-unread"
                    href="lire_notification.php?id=<?= $notification['id']; ?>" title="Cliquer pour marquer comme lue">

                    <i class="fa-solid fa-circle-exclamation"></i>
                    Non lue

                </a>

                <?php } else { ?>

                <span class="notification-status notification-read">

                    <i class="fa-solid fa-check-circle"></i>
                    Lue

                </span>

                <?php } ?>
            </td>

            <td><?= e($notification['date_notification']); ?></td>


        </tr>

        <?php } ?>

    </table>
</div>

<?php } ?>