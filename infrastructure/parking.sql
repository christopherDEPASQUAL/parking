-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : app_db
-- Généré le : lun. 22 déc. 2025 à 15:51
-- Version du serveur : 8.0.44
-- Version de PHP : 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `parking`
--

-- --------------------------------------------------------

--
-- Structure de la table `opening_hours`
--

CREATE TABLE `opening_hours` (
  `id` bigint NOT NULL,
  `parking_id` char(36) NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `end_day_of_week` tinyint NOT NULL,
  `open_time` time NOT NULL,
  `close_time` time NOT NULL
) ;

--
-- Déchargement des données de la table `opening_hours`
--

INSERT INTO `opening_hours` (`id`, `parking_id`, `day_of_week`, `end_day_of_week`, `open_time`, `close_time`) VALUES
(6, '489da210-c70e-434c-afe1-04f72cdcabaa', 1, 0, '00:00:00', '23:59:00'),
(7, 'd86ec7b5-5fbb-4e2c-a15c-fddc3f29facc', 0, 6, '00:00:00', '23:59:00'),
(10, '2b375fae-9d78-4b8a-a852-b5ea3706b1f9', 1, 6, '00:00:00', '23:59:00'),
(13, '3ae4a3e4-fa9b-462f-9706-72acf9a89fee', 1, 0, '00:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `parkings`
--

CREATE TABLE `parkings` (
  `id` char(36) NOT NULL,
  `owner_id` char(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) NOT NULL,
  `description` text,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `capacity` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `parkings`
--

INSERT INTO `parkings` (`id`, `owner_id`, `name`, `address`, `description`, `latitude`, `longitude`, `capacity`, `created_at`, `updated_at`) VALUES
('2b375fae-9d78-4b8a-a852-b5ea3706b1f9', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', 'Auchan2', '13 rue du cèdre 94400', 'AAA', 48.8533000, 2.3533000, 150, '2025-12-21 23:03:30', '2025-12-22 02:09:30'),
('3ae4a3e4-fa9b-462f-9706-72acf9a89fee', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', 'Auchan', '10 allée du cèdre 94400', '', 48.8566000, 2.3522000, 100, '2025-12-21 23:02:40', '2025-12-22 02:16:43'),
('489da210-c70e-434c-afe1-04f72cdcabaa', 'b11303a0-9280-442e-8a38-8073a9bbe42c', 'Paris Nord', '17 rue des rois', 'ZZZZZZ', 28.1000000, 20.1000000, 80, '2025-12-22 01:08:16', '2025-12-22 01:09:59'),
('d86ec7b5-5fbb-4e2c-a15c-fddc3f29facc', 'b11303a0-9280-442e-8a38-8073a9bbe42c', 'Zenith', '20 avenu charles', 'XXXX', 40.0000000, 30.0000000, 27, '2025-12-22 01:18:15', '2025-12-22 01:18:15');

-- --------------------------------------------------------

--
-- Structure de la table `parking_pricing_plans`
--

CREATE TABLE `parking_pricing_plans` (
  `parking_id` char(36) NOT NULL,
  `plan_json` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `parking_pricing_plans`
--

INSERT INTO `parking_pricing_plans` (`parking_id`, `plan_json`, `created_at`, `updated_at`) VALUES
('2b375fae-9d78-4b8a-a852-b5ea3706b1f9', '{\"tiers\": [{\"upToMinutes\": 60, \"pricePerStepCents\": 1000}], \"stepMinutes\": 15, \"subscriptionPrices\": {\"evening\": 20000, \"weekend\": 90000}, \"overstayPenaltyCents\": 2000, \"defaultPricePerStepCents\": 1000}', '2025-12-21 23:03:30', '2025-12-22 02:09:30'),
('3ae4a3e4-fa9b-462f-9706-72acf9a89fee', '{\"tiers\": [], \"stepMinutes\": 15, \"subscriptionPrices\": {\"full\": 30000, \"evening\": 1222, \"weekend\": 3000}, \"overstayPenaltyCents\": 2000, \"defaultPricePerStepCents\": 1000}', '2025-12-21 23:02:40', '2025-12-22 02:16:43'),
('489da210-c70e-434c-afe1-04f72cdcabaa', '{\"tiers\": [], \"stepMinutes\": 15, \"subscriptionPrices\": {}, \"overstayPenaltyCents\": 2000, \"defaultPricePerStepCents\": 0}', '2025-12-22 01:08:16', '2025-12-22 01:09:59'),
('d86ec7b5-5fbb-4e2c-a15c-fddc3f29facc', '{\"tiers\": [], \"stepMinutes\": 15, \"subscriptionPrices\": {}, \"overstayPenaltyCents\": 2000, \"defaultPricePerStepCents\": 0}', '2025-12-22 01:18:15', '2025-12-22 01:18:15');

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `reservation_id` char(36) DEFAULT NULL,
  `subscription_id` char(36) DEFAULT NULL,
  `stationing_id` char(36) DEFAULT NULL,
  `status` enum('pending','approved','refused') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `provider_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `reservation_id`, `subscription_id`, `stationing_id`, `status`, `amount`, `provider_reference`, `created_at`) VALUES
('2df18f75-4aef-424e-921b-978316396b85', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', NULL, 'b252b7bf-be5c-41d3-94ad-e4aa4680bfb5', NULL, 'approved', 15.00, 'mock-551c9e6eb8ff', '2025-12-22 01:47:25'),
('678e5973-74bc-4a15-947f-0f8012de09ee', 'b11303a0-9280-442e-8a38-8073a9bbe42c', NULL, NULL, '6f98de14-e4a8-4c81-b720-141e8ab3543f', 'approved', 40.00, 'mock-1f0c5cba4233', '2025-12-22 02:31:15'),
('78cd098b-790c-4e7b-816e-6d7cf6f845a7', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', '1fd15913-21f9-4f41-a705-718fb1111292', NULL, NULL, 'approved', 0.00, 'mock-3f78088f98ed', '2025-12-22 01:28:11'),
('894e5d63-524e-4a31-bbb5-0f2e83d33cff', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', '8611b365-1318-4447-9d00-d7359c362b15', NULL, NULL, 'approved', 0.00, 'mock-3d3d84031c4a', '2025-12-22 01:14:22'),
('949be32e-b4a5-4e3b-b008-907cdecccffd', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', '6ec14c2a-6387-4e7a-bf60-9d4aa224ee52', NULL, NULL, 'approved', 0.00, 'mock-b224a8b6ef36', '2025-12-22 01:23:33'),
('b13ba71f-a392-43cf-bfeb-1217a3fbb669', 'b11303a0-9280-442e-8a38-8073a9bbe42c', 'af1b0d21-23de-46e7-815c-191f5d1d1998', NULL, NULL, 'approved', 0.00, 'mock-f4412bd886f9', '2025-12-21 23:54:48'),
('b58f2d45-f4aa-4acb-8454-7be7c9e560b3', 'b11303a0-9280-442e-8a38-8073a9bbe42c', 'cf9c3330-6616-4ec6-84a3-4d99e2f15c63', NULL, NULL, 'approved', 40.00, 'mock-7a56a0f73d22', '2025-12-22 02:11:23'),
('bb188cbb-b57a-45fc-8bf2-1960157d9420', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', '1699e2fb-c9fb-4d4e-8e1d-767bd26f8ea7', NULL, NULL, 'approved', 0.00, 'mock-9555446819d8', '2025-12-21 23:43:36'),
('c6cdb658-3959-4605-ac61-5eee56484638', 'b11303a0-9280-442e-8a38-8073a9bbe42c', NULL, NULL, '07eea793-b195-477b-bfb9-4d77f56de4c4', 'approved', 0.00, 'mock-e10575f55294', '2025-12-22 00:24:57'),
('c70fb169-e1cb-49d9-bb26-c2c014643ec3', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', 'd45c88bf-0a2c-4ad8-8bc1-9f81831f11a2', NULL, NULL, 'approved', 0.00, 'mock-9a0f58ea9633', '2025-12-21 23:29:05');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `parking_id` char(36) NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `status` enum('pending_payment','pending','confirmed','cancelled','completed','payment_failed') NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'EUR',
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `parking_id`, `starts_at`, `ends_at`, `status`, `price`, `currency`, `cancelled_at`, `cancellation_reason`, `created_at`) VALUES
('1699e2fb-c9fb-4d4e-8e1d-767bd26f8ea7', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', '2b375fae-9d78-4b8a-a852-b5ea3706b1f9', '2025-12-22 00:44:00', '2025-12-22 01:00:00', 'confirmed', 0.00, 'EUR', NULL, NULL, '2025-12-21 23:43:33'),
('1fd15913-21f9-4f41-a705-718fb1111292', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', 'd86ec7b5-5fbb-4e2c-a15c-fddc3f29facc', '2025-12-22 03:27:00', '2025-12-22 09:27:00', 'confirmed', 0.00, 'EUR', NULL, NULL, '2025-12-22 01:28:08'),
('6ec14c2a-6387-4e7a-bf60-9d4aa224ee52', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', 'd86ec7b5-5fbb-4e2c-a15c-fddc3f29facc', '2025-12-22 01:23:00', '2025-12-22 02:23:00', 'confirmed', 0.00, 'EUR', NULL, NULL, '2025-12-22 01:23:29'),
('8611b365-1318-4447-9d00-d7359c362b15', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', '489da210-c70e-434c-afe1-04f72cdcabaa', '2025-12-22 01:13:00', '2025-12-22 02:13:00', 'confirmed', 0.00, 'EUR', NULL, NULL, '2025-12-22 01:14:16'),
('af1b0d21-23de-46e7-815c-191f5d1d1998', 'b11303a0-9280-442e-8a38-8073a9bbe42c', '2b375fae-9d78-4b8a-a852-b5ea3706b1f9', '2025-12-22 00:00:00', '2025-12-22 01:54:00', 'completed', 0.00, 'EUR', NULL, NULL, '2025-12-21 23:54:47'),
('cf9c3330-6616-4ec6-84a3-4d99e2f15c63', 'b11303a0-9280-442e-8a38-8073a9bbe42c', '2b375fae-9d78-4b8a-a852-b5ea3706b1f9', '2025-12-22 02:10:00', '2025-12-22 03:10:00', 'completed', 40.00, 'EUR', NULL, NULL, '2025-12-22 02:11:23'),
('d45c88bf-0a2c-4ad8-8bc1-9f81831f11a2', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', '2b375fae-9d78-4b8a-a852-b5ea3706b1f9', '2025-12-22 10:27:00', '2025-12-22 15:27:00', 'cancelled', 0.00, 'EUR', '2025-12-22 00:04:09', NULL, '2025-12-21 23:28:59');

-- --------------------------------------------------------

--
-- Structure de la table `stationings`
--

CREATE TABLE `stationings` (
  `id` char(36) NOT NULL,
  `parking_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `reservation_id` char(36) DEFAULT NULL,
  `subscription_id` char(36) DEFAULT NULL,
  `entered_at` datetime NOT NULL,
  `exited_at` datetime DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ;

--
-- Déchargement des données de la table `stationings`
--

INSERT INTO `stationings` (`id`, `parking_id`, `user_id`, `reservation_id`, `subscription_id`, `entered_at`, `exited_at`, `amount`) VALUES
('07eea793-b195-477b-bfb9-4d77f56de4c4', '2b375fae-9d78-4b8a-a852-b5ea3706b1f9', 'b11303a0-9280-442e-8a38-8073a9bbe42c', 'af1b0d21-23de-46e7-815c-191f5d1d1998', NULL, '2025-12-22 00:00:26', '2025-12-22 00:24:56', 0.00),
('6f98de14-e4a8-4c81-b720-141e8ab3543f', '2b375fae-9d78-4b8a-a852-b5ea3706b1f9', 'b11303a0-9280-442e-8a38-8073a9bbe42c', 'cf9c3330-6616-4ec6-84a3-4d99e2f15c63', NULL, '2025-12-22 02:12:49', '2025-12-22 02:31:12', 40.00),
('bf4d2849-f37d-4d84-a9dc-602fdb101b64', '489da210-c70e-434c-afe1-04f72cdcabaa', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', '8611b365-1318-4447-9d00-d7359c362b15', NULL, '2025-12-22 01:15:52', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `parking_id` char(36) NOT NULL,
  `offer_id` char(36) NOT NULL,
  `starts_at` date NOT NULL,
  `ends_at` date NOT NULL,
  `status` enum('active','paused','cancelled','expired') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `parking_id`, `offer_id`, `starts_at`, `ends_at`, `status`, `created_at`) VALUES
('b252b7bf-be5c-41d3-94ad-e4aa4680bfb5', '11e631b1-2af7-44c7-8bdc-d8c3fd817baf', '489da210-c70e-434c-afe1-04f72cdcabaa', 'd6d5180d-7943-45f0-a6aa-ea2d411a2a89', '2025-12-22', '2026-01-22', 'active', '2025-12-22 01:47:25');

-- --------------------------------------------------------

--
-- Structure de la table `subscription_offers`
--

CREATE TABLE `subscription_offers` (
  `id` char(36) NOT NULL,
  `parking_id` char(36) NOT NULL,
  `label` varchar(150) NOT NULL,
  `type` enum('full','weekend','evening','custom') NOT NULL,
  `price_cents` int NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `subscription_offers`
--

INSERT INTO `subscription_offers` (`id`, `parking_id`, `label`, `type`, `price_cents`, `status`, `created_at`, `updated_at`) VALUES
('7502d9d3-b313-47f4-a5a7-34e94afdd8ec', '2b375fae-9d78-4b8a-a852-b5ea3706b1f9', 'Test offer', 'evening', 2000, 'active', '2025-12-22 02:10:41', '2025-12-22 02:10:41'),
('d6d5180d-7943-45f0-a6aa-ea2d411a2a89', '489da210-c70e-434c-afe1-04f72cdcabaa', 'Reduction Spééciale', 'evening', 1500, 'active', '2025-12-22 01:11:23', '2025-12-22 01:11:23');

-- --------------------------------------------------------

--
-- Structure de la table `subscription_offer_slots`
--

CREATE TABLE `subscription_offer_slots` (
  `id` bigint NOT NULL,
  `offer_id` char(36) NOT NULL,
  `start_day_of_week` tinyint NOT NULL,
  `end_day_of_week` tinyint NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ;

--
-- Déchargement des données de la table `subscription_offer_slots`
--

INSERT INTO `subscription_offer_slots` (`id`, `offer_id`, `start_day_of_week`, `end_day_of_week`, `start_time`, `end_time`) VALUES
(1, 'd6d5180d-7943-45f0-a6aa-ea2d411a2a89', 1, 6, '00:00:00', '23:59:00'),
(2, '7502d9d3-b313-47f4-a5a7-34e94afdd8ec', 1, 0, '00:00:00', '23:59:00');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('admin','client','proprietor') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `created_at`, `updated_at`) VALUES
('11e631b1-2af7-44c7-8bdc-d8c3fd817baf', 'tie@gmail.com', '$2y$10$Ms0yro6uRMgLy7RY09Bn7O1QKAlaT/bvylo4S4E1u/z.QIqhdE9Iy', 'carlos', 'Tie', 'proprietor', '2025-12-21 22:52:14', NULL),
('6216b0b8-db03-41b6-a7bb-59e2338d40a7', 'correction@gmail.com', '$2y$10$XRLlzCjS71/Pbwy3rbhhAustGiWV8iHsh/zMKN9gNd.mmxEjS5doe', 'correction', 'vérification', 'client', '2025-12-22 15:46:49', NULL),
('942a6162-5fb5-452a-bfe4-cba17cf6da58', 'florian@gmail.com', '$2y$10$R6g3GmDLWdPUV9HACya5B.m5Za/JCoWV8J6HPdQD4Jp5K7XBoRFry', 'florian', 'Victor', 'client', '2025-12-22 07:19:36', NULL),
('b11303a0-9280-442e-8a38-8073a9bbe42c', 'christopherdepasqual10@gmail.com', '$2y$10$br/1PASR9FbL.S6W6c/D1OT962WBW4uyv1F8M9RuHfic1SDmhGq1e', 'Christopher', 'DE PASQUAL', 'proprietor', '2025-12-21 22:37:22', NULL),
('ff2322ac-92d4-41d0-8ecf-8ca12d52e4d4', 'victor@gmail.com', '$2y$10$kYUjVYMCntTEf2hAee6hKeUCtua6YOoDZZjeR3LcCOCCVZGclcjqO', 'Victor', 'Chabeau', 'client', '2025-12-22 15:20:07', NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `opening_hours`
--
ALTER TABLE `opening_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`);

--
-- Index pour la table `parkings`
--
ALTER TABLE `parkings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Index pour la table `parking_pricing_plans`
--
ALTER TABLE `parking_pricing_plans`
  ADD PRIMARY KEY (`parking_id`);

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `subscription_id` (`subscription_id`),
  ADD KEY `stationing_id` (`stationing_id`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parking_id` (`parking_id`);

--
-- Index pour la table `stationings`
--
ALTER TABLE `stationings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `subscription_id` (`subscription_id`);

--
-- Index pour la table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parking_id` (`parking_id`),
  ADD KEY `offer_id` (`offer_id`);

--
-- Index pour la table `subscription_offers`
--
ALTER TABLE `subscription_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`);

--
-- Index pour la table `subscription_offer_slots`
--
ALTER TABLE `subscription_offer_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offer_id` (`offer_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `opening_hours`
--
ALTER TABLE `opening_hours`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `subscription_offer_slots`
--
ALTER TABLE `subscription_offer_slots`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `opening_hours`
--
ALTER TABLE `opening_hours`
  ADD CONSTRAINT `opening_hours_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`);

--
-- Contraintes pour la table `parkings`
--
ALTER TABLE `parkings`
  ADD CONSTRAINT `parkings_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `parking_pricing_plans`
--
ALTER TABLE `parking_pricing_plans`
  ADD CONSTRAINT `parking_pricing_plans_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`);

--
-- Contraintes pour la table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`),
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`),
  ADD CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`stationing_id`) REFERENCES `stationings` (`id`);

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`);

--
-- Contraintes pour la table `stationings`
--
ALTER TABLE `stationings`
  ADD CONSTRAINT `stationings_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`),
  ADD CONSTRAINT `stationings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `stationings_ibfk_3` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`),
  ADD CONSTRAINT `stationings_ibfk_4` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`);

--
-- Contraintes pour la table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`),
  ADD CONSTRAINT `subscriptions_ibfk_3` FOREIGN KEY (`offer_id`) REFERENCES `subscription_offers` (`id`);

--
-- Contraintes pour la table `subscription_offers`
--
ALTER TABLE `subscription_offers`
  ADD CONSTRAINT `subscription_offers_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parkings` (`id`);

--
-- Contraintes pour la table `subscription_offer_slots`
--
ALTER TABLE `subscription_offer_slots`
  ADD CONSTRAINT `subscription_offer_slots_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `subscription_offers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
