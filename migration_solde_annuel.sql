-- Suivi annuel des droits de conge annuel.
-- A executer une seule fois dans la base gestion_conges.

CREATE TABLE IF NOT EXISTS soldes_annuels (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Initialisation propre de l'annee actuelle.
-- Les seuls jours deduits sont les conges annuels deja valides cette annee.
SET @annee_courante = YEAR(CURDATE());

INSERT INTO soldes_annuels (
    employe_id, annee, droits_annuels, jours_consommes,
    reliquat_annee_precedente, solde_disponible
)
SELECT
    e.id,
    @annee_courante,
    22,
    COALESCE(SUM(
        CASE
            WHEN c.statut = 'Valide' AND c.type_conge = 'Congé annuel'
            THEN c.nombre_jours
            ELSE 0
        END
    ), 0),
    0,
    GREATEST(22 - COALESCE(SUM(
        CASE
            WHEN c.statut = 'Valide' AND c.type_conge = 'Congé annuel'
            THEN c.nombre_jours
            ELSE 0
        END
    ), 0), 0)
FROM employes e
LEFT JOIN conges c
    ON c.employe_id = e.id
   AND YEAR(c.date_debut) = @annee_courante
GROUP BY e.id
ON DUPLICATE KEY UPDATE employe_id = VALUES(employe_id);

-- Synchroniser les anciennes colonnes utilisees par l'affichage existant.
UPDATE employes e
INNER JOIN soldes_annuels sa
    ON sa.employe_id = e.id
   AND sa.annee = @annee_courante
SET e.solde_annuel = sa.droits_annuels,
    e.reste_annee_precedente = sa.reliquat_annee_precedente,
    e.solde_total = sa.solde_disponible,
    e.derniere_maj_solde = @annee_courante;

INSERT INTO parametres (id, derniere_maj_solde)
VALUES (1, @annee_courante)
ON DUPLICATE KEY UPDATE derniere_maj_solde = @annee_courante;
