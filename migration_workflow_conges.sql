-- Migration du workflow de validation des demandes de conge.
-- A executer une seule fois dans la base gestion_conges.

ALTER TABLE conges
    MODIFY statut ENUM('En attente', 'Valide', 'Refuse', 'Annule',
                       'en_attente_chef', 'en_attente_admin', 'validee', 'refusee')
    NOT NULL DEFAULT 'en_attente_chef';

UPDATE conges SET statut = 'en_attente_chef' WHERE statut = 'En attente';
UPDATE conges SET statut = 'validee' WHERE statut = 'Valide';
UPDATE conges SET statut = 'refusee' WHERE statut = 'Refuse';

ALTER TABLE conges
    MODIFY statut ENUM('en_attente_chef', 'en_attente_admin', 'validee', 'refusee', 'Annule')
    NOT NULL DEFAULT 'en_attente_chef';
