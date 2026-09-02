-- Ajouter le statut du compte utilisateur.
-- A executer une seule fois dans la base gestion_conges.

ALTER TABLE utilisateurs
    ADD COLUMN statut ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif'
    AFTER role;
