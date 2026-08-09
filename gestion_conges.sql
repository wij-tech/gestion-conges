-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 05 août 2026 à 16:52
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_conges`
--

-- --------------------------------------------------------

--
-- Structure de la table `conges`
--

CREATE TABLE `conges` (
  `id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `type_conge` varchar(50) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nombre_jours` int(11) NOT NULL,
  `motif` text NOT NULL,
  `statut` enum('En attente','Valide','Refuse','Annule') DEFAULT 'En attente',
  `date_demande` datetime DEFAULT current_timestamp(),
  `date_traitement` datetime DEFAULT NULL,
  `chef_division_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `conges`
--

INSERT INTO `conges` (`id`, `employe_id`, `type_conge`, `date_debut`, `date_fin`, `nombre_jours`, `motif`, `statut`, `date_demande`, `date_traitement`, `chef_division_id`) VALUES
(1, 3, 'Congé annuel', '2026-07-02', '2026-07-30', 29, 'all', 'Valide', '2026-07-19 12:49:07', '2026-07-19 13:10:44', 5),
(2, 4, 'Congé annuel', '2026-07-21', '2026-07-25', 5, 'aze', 'Valide', '2026-07-21 14:21:02', '2026-07-21 14:21:38', 5);

-- --------------------------------------------------------

--
-- Structure de la table `divisions`
--

CREATE TABLE `divisions` (
  `id` int(11) NOT NULL,
  `nom_division` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `divisions`
--

INSERT INTO `divisions` (`id`, `nom_division`) VALUES
(1, 'Division des Ressources Humaines  Division des Finances  Division des Affaires Générales  Division d');

-- --------------------------------------------------------

--
-- Structure de la table `employes`
--

CREATE TABLE `employes` (
  `id` int(11) NOT NULL,
  `matricule` varchar(50) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_embauche` date NOT NULL,
  `division_id` int(11) DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `solde_annuel` int(11) DEFAULT 22,
  `reste_annee_precedente` int(11) DEFAULT 0,
  `solde_total` int(11) DEFAULT 22,
  `derniere_maj_solde` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `employes`
--

INSERT INTO `employes` (`id`, `matricule`, `nom`, `prenom`, `email`, `telephone`, `date_embauche`, `division_id`, `utilisateur_id`, `solde_annuel`, `reste_annee_precedente`, `solde_total`, `derniere_maj_solde`) VALUES
(3, 'EMP001', 'himi', 'manal', 'manal@gmail.com', '0655874589', '2026-07-15', 1, 4, 22, 81, 103, NULL),
(4, 'EMP002', 'zerhouni', 'samira', 'zerhounisamira16@gmail.com', '0585421589', '2023-02-02', 1, 5, 22, 110, 127, NULL),
(5, 'EMP003', 'fellahi', 'ilyas', 'ilyas.fellahi.fi@gmail.com', '0666872686', '2024-02-02', 1, 6, 22, 22, 44, NULL),
(7, 'EMP004', 'bennani', 'wijdane', 'wijdane@gmail.com', '0521875124', '2026-07-02', 1, 8, 22, 22, 44, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `historique_maj_solde`
--

CREATE TABLE `historique_maj_solde` (
  `id` int(11) NOT NULL,
  `annee` int(11) NOT NULL,
  `date_execution` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `titre` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type_notification` enum('Application','Email') NOT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `date_notification` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `utilisateur_id`, `titre`, `message`, `type_notification`, `lu`, `date_notification`) VALUES
(5, 4, 'Mise à jour du solde annuel', 'Votre nouveau solde de congés a été calculé pour la nouvelle année.', '', 1, '2026-07-19 12:32:53'),
(7, 4, 'Mise à jour du solde annuel', 'Votre nouveau solde de congés a été calculé pour la nouvelle année.', '', 1, '2026-07-19 12:35:16'),
(8, 5, 'Mise à jour du solde annuel', 'Votre nouveau solde de congés a été calculé pour la nouvelle année.', '', 1, '2026-07-19 12:35:16'),
(9, 4, 'Congé validé', 'Votre demande de congé a été validée.', '', 1, '2026-07-19 13:10:44'),
(10, 4, 'Mise à jour du solde annuel', 'Votre nouveau solde de congés a été calculé pour la nouvelle année.', '', 1, '2026-07-20 23:09:58'),
(11, 5, 'Mise à jour du solde annuel', 'Votre nouveau solde de congés a été calculé pour la nouvelle année.', '', 1, '2026-07-20 23:09:58'),
(12, 6, 'Mise à jour du solde annuel', 'Votre nouveau solde de congés a été calculé pour la nouvelle année.', '', 1, '2026-07-20 23:09:58'),
(13, 8, 'Mise à jour du solde annuel', 'Votre nouveau solde de congés a été calculé pour la nouvelle année.', '', 0, '2026-07-20 23:09:58'),
(14, 5, 'Congé validé', 'Votre demande de congé a été validée.', '', 0, '2026-07-21 14:21:38');

-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

CREATE TABLE `parametres` (
  `id` int(11) NOT NULL,
  `derniere_maj_solde` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `parametres`
--

INSERT INTO `parametres` (`id`, `derniere_maj_solde`) VALUES
(1, '2026');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employe','chef_division') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$FGPjvL1WUGDRjjvs0315iO9Dlx5jvJ.XqJWgRiHi0lVSmKcwxHOEG', 'admin'),
(4, 'himi.manal', '$2y$10$04EnoxmbVwHa4pStcuBP8eKpFSw/loLAIE7V6f7RileFhwQBFbk16', 'employe'),
(5, 'zerhouni.samira', '$2y$10$hc8vh.ZtDhnXXyZM7cHyt.xcuo..TG57OTxiIuefyvVyK8mfTgCN2', 'employe'),
(6, 'fellahi.ilyas', '$2y$10$cpDacCa3XlgtTTgccO6Eq.6FkYQ1a2hC6Xnw/HR6eb7zIYYFr9JOG', 'chef_division'),
(8, 'bennani.wijdane', '$2y$10$aNGWKRhbBRdjLwTw4mnidOpO5kHhqUwGk9nfD1Z2TmppUz0JAfxNq', 'employe');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `conges`
--
ALTER TABLE `conges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employe_id` (`employe_id`);

--
-- Index pour la table `divisions`
--
ALTER TABLE `divisions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `employes`
--
ALTER TABLE `employes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD KEY `division_id` (`division_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `historique_maj_solde`
--
ALTER TABLE `historique_maj_solde`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_utilisateur` (`utilisateur_id`);

--
-- Index pour la table `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `conges`
--
ALTER TABLE `conges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `employes`
--
ALTER TABLE `employes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `historique_maj_solde`
--
ALTER TABLE `historique_maj_solde`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `conges`
--
ALTER TABLE `conges`
  ADD CONSTRAINT `conges_ibfk_1` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`);

--
-- Contraintes pour la table `employes`
--
ALTER TABLE `employes`
  ADD CONSTRAINT `employes_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`),
  ADD CONSTRAINT `employes_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
