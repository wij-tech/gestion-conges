<?php

function garantir_table_soldes_annuels(PDO $pdo): void
{
    static $tableVerifiee = false;
    if ($tableVerifiee) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS soldes_annuels (
            id INT NOT NULL AUTO_INCREMENT,
            employe_id INT NOT NULL,
            annee YEAR NOT NULL,
            droits_annuels INT NOT NULL DEFAULT 22,
            jours_consommes INT NOT NULL DEFAULT 0,
            reliquat_annee_precedente INT NOT NULL DEFAULT 0,
            solde_disponible INT NOT NULL DEFAULT 22,
            date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_employe_annee (employe_id, annee),
            KEY idx_soldes_annuels_employe (employe_id),
            CONSTRAINT fk_soldes_annuels_employe
                FOREIGN KEY (employe_id) REFERENCES employes(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
    $tableVerifiee = true;
}

function obtenir_solde_annuel(PDO $pdo, int $employeId, int $annee): array
{
    garantir_table_soldes_annuels($pdo);

    $stmt = $pdo->prepare(
        'SELECT * FROM soldes_annuels WHERE employe_id = ? AND annee = ?'
    );
    $stmt->execute([$employeId, $annee]);
    $solde = $stmt->fetch();

    if ($solde) {
        return $solde;
    }

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(nombre_jours), 0)
         FROM conges
         WHERE employe_id = ?
           AND type_conge = 'Congé annuel'
           AND statut = 'validee'
           AND YEAR(date_debut) = ?"
    );
    $stmt->execute([$employeId, $annee]);
    $joursConsommes = (int) $stmt->fetchColumn();

    $reliquat = 0;
    if ($annee > 0) {
        $stmt = $pdo->prepare(
            'SELECT solde_disponible FROM soldes_annuels WHERE employe_id = ? AND annee = ?'
        );
        $stmt->execute([$employeId, $annee - 1]);
        $reliquat = max(0, (int) $stmt->fetchColumn());
    }

    $droits = 22;
    $disponible = max(0, $reliquat + $droits - $joursConsommes);
    $stmt = $pdo->prepare(
        'INSERT INTO soldes_annuels
            (employe_id, annee, droits_annuels, jours_consommes,
             reliquat_annee_precedente, solde_disponible)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$employeId, $annee, $droits, $joursConsommes, $reliquat, $disponible]);

    return [
        'employe_id' => $employeId,
        'annee' => $annee,
        'droits_annuels' => $droits,
        'jours_consommes' => $joursConsommes,
        'reliquat_annee_precedente' => $reliquat,
        'solde_disponible' => $disponible
    ];
}

function synchroniser_ancien_solde(PDO $pdo, int $employeId, int $solde): void
{
    $stmt = $pdo->prepare('UPDATE employes SET solde_total = ? WHERE id = ?');
    $stmt->execute([$solde, $employeId]);
}
