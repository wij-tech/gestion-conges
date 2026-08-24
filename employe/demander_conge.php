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
$messageErreur = '';


// Traitement du formulaire
if (isset($_POST['envoyer'])) {

    $type_conge = $_POST['type_conge'] ?? '';
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin = $_POST['date_fin'] ?? '';
    $motif = trim($_POST['motif'] ?? '');
    $typesAutorises = ['Congé annuel', 'Congé maladie', 'Congé exceptionnel'];
    $certificatMedical = null;
    $fichierCree = null;

    if (!in_array($type_conge, $typesAutorises, true) || $motif === '') {
        $messageErreur = 'Veuillez renseigner une demande de congé valide.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_debut)
        || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_fin) || $date_fin < $date_debut) {
        $messageErreur = 'La date de fin doit être postérieure ou égale à la date de début.';
    } else {
        $nombre_jours = (new DateTime($date_debut))->diff(new DateTime($date_fin))->days + 1;

        if ($type_conge === 'Congé annuel' && $nombre_jours > (int) $employe['solde_total']) {
            $messageErreur = 'Votre solde de congés annuels est insuffisant.';
        } elseif ($type_conge === 'Congé exceptionnel'
            && ((int) $employe['solde_total'] > 0 || $nombre_jours > 10)) {
            $messageErreur = 'Le congé exceptionnel est disponible uniquement lorsque le solde annuel est épuisé, pour 10 jours maximum.';
        } elseif ($type_conge === 'Congé maladie') {
            $fichier = $_FILES['certificat_medical'] ?? null;
            if (!$fichier || $fichier['error'] !== UPLOAD_ERR_OK) {
                $messageErreur = 'Un certificat médical au format PDF est obligatoire pour un congé maladie.';
            } elseif ($fichier['size'] > 5 * 1024 * 1024) {
                $messageErreur = 'Le certificat médical ne doit pas dépasser 5 Mo.';
            } else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($fichier['tmp_name']);
                if ($mime !== 'application/pdf') {
                    $messageErreur = 'Seuls les fichiers PDF sont acceptés pour le certificat médical.';
                } else {
                    $dossier = dirname(__DIR__) . '/uploads/certificats';
                    if (!is_dir($dossier) && !mkdir($dossier, 0755, true)) {
                        $messageErreur = 'Impossible de préparer le stockage du certificat médical.';
                    } else {
                        $certificatMedical = bin2hex(random_bytes(16)) . '.pdf';
                        $fichierCree = $dossier . '/' . $certificatMedical;
                        if (!move_uploaded_file($fichier['tmp_name'], $fichierCree)) {
                            $messageErreur = 'Impossible d’enregistrer le certificat médical.';
                        }
                    }
                }
            }
        }

        if ($messageErreur === '') {
            try {
                $sql = "INSERT INTO conges (employe_id, chef_division_id, type_conge, date_debut, date_fin, nombre_jours, motif, certificat_medical, statut)
                        VALUES (?, NULL, ?, ?, ?, ?, ?, ?, 'En attente')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$employe['id'], $type_conge, $date_debut, $date_fin, $nombre_jours, $motif, $certificatMedical]);
                $messageSucces = 'Votre demande de congé a été envoyée avec succès.';
            } catch (Throwable $exception) {
                if ($fichierCree && is_file($fichierCree)) {
                    unlink($fichierCree);
                }
                $messageErreur = 'Impossible d’enregistrer votre demande. Veuillez réessayer.';
            }
        }
    }
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

<?php if ($messageErreur !== '') { ?>
    <div class="flash-message flash-error" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($messageErreur); ?></span>
    </div>
<?php } ?>

<p>
    Votre solde actuel :
    <strong><?= $employe['solde_total']; ?> jours</strong>
</p>

<form method="POST" enctype="multipart/form-data">

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

    <option value="Congé exceptionnel" <?= (int) $employe['solde_total'] > 0 ? 'disabled' : ''; ?>>
        Congé exceptionnel
    </option>

</select>

<?php if ((int) $employe['solde_total'] > 0) { ?>
    <small class="form-hint">Le congé exceptionnel sera disponible lorsque votre solde annuel sera épuisé.</small>
<?php } ?>

<div id="certificat-medical-field" class="conditional-field" hidden>
    <label>
        <i class="fa-solid fa-file-pdf"></i>
        Certificat médical (PDF, 5 Mo maximum)
    </label>
    <input type="file" name="certificat_medical" accept="application/pdf">
</div>


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

<script>
const leaveType = document.querySelector('[name="type_conge"]');
const medicalField = document.getElementById('certificat-medical-field');
const medicalFile = medicalField.querySelector('input');
function toggleMedicalCertificate() {
    const isMedical = leaveType.value === 'Congé maladie';
    medicalField.hidden = !isMedical;
    medicalFile.required = isMedical;
    if (!isMedical) medicalFile.value = '';
}
leaveType.addEventListener('change', toggleMedicalCertificate);
toggleMedicalCertificate();
</script>
