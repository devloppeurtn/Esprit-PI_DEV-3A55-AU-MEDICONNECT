-- ============================================================================
-- MEDICONNECT - Données d'Exemple
-- ============================================================================
-- Ce fichier contient des données d'exemple pour tester l'application
-- ============================================================================

USE mediconnect;

-- ============================================================================
-- DONNÉES: Utilisateurs
-- ============================================================================

-- Admin
INSERT INTO utilisateur (email, mot_de_passe_hash, nom_complet, role, statut, date_creation, discr, email_verified) VALUES
('admin@mediconnect.com', '$2y$13$hashed_password_here', 'Administrateur Principal', 'ROLE_ADMIN', 'ACTIF', NOW(), 'admin', 1);

-- Médecins
INSERT INTO utilisateur (email, mot_de_passe_hash, nom_complet, role, statut, date_creation, discr, specialite, adresse_cabinet, numero_licence, telephone, email_verified) VALUES
('dr.smith@mediconnect.com', '$2y$13$hashed_password_here', 'Dr. John Smith', 'ROLE_MEDECIN', 'ACTIF', NOW(), 'medecin', 'Cardiologie', '123 Rue de la Santé, Tunis', 'MED-2024-001', '+216 71 123 456', 1),
('dr.martin@mediconnect.com', '$2y$13$hashed_password_here', 'Dr. Marie Martin', 'ROLE_MEDECIN', 'ACTIF', NOW(), 'medecin', 'Pédiatrie', '456 Avenue Habib Bourguiba, Tunis', 'MED-2024-002', '+216 71 234 567', 1),
('dr.ben@mediconnect.com', '$2y$13$hashed_password_here', 'Dr. Ahmed Ben Ali', 'ROLE_MEDECIN', 'ACTIF', NOW(), 'medecin', 'Dermatologie', '789 Rue de la République, Sfax', 'MED-2024-003', '+216 74 345 678', 1);

-- Patients
INSERT INTO utilisateur (email, mot_de_passe_hash, nom_complet, role, statut, date_creation, discr, telephone, date_naissance, adresse, email_verified) VALUES
('patient1@example.com', '$2y$13$hashed_password_here', 'Mohamed Trabelsi', 'ROLE_PATIENT', 'ACTIF', NOW(), 'patient', '+216 98 123 456', '1990-05-15', 'Ariana, Tunisie', 1),
('patient2@example.com', '$2y$13$hashed_password_here', 'Fatma Gharbi', 'ROLE_PATIENT', 'ACTIF', NOW(), 'patient', '+216 98 234 567', '1985-08-22', 'La Marsa, Tunisie', 1),
('patient3@example.com', '$2y$13$hashed_password_here', 'Ali Mansour', 'ROLE_PATIENT', 'ACTIF', NOW(), 'patient', '+216 98 345 678', '1995-12-10', 'Sousse, Tunisie', 1);

-- Secrétaires
INSERT INTO utilisateur (email, mot_de_passe_hash, nom_complet, role, statut, date_creation, discr, telephone, medecin_id, email_verified) VALUES
('secretaire1@mediconnect.com', '$2y$13$hashed_password_here', 'Leila Hamdi', 'ROLE_SECRETAIRE', 'ACTIF', NOW(), 'secretaire', '+216 71 456 789', 2, 1),
('secretaire2@mediconnect.com', '$2y$13$hashed_password_here', 'Sonia Kacem', 'ROLE_SECRETAIRE', 'ACTIF', NOW(), 'secretaire', '+216 71 567 890', 3, 1);

-- ============================================================================
-- DONNÉES: Dossiers Médicaux
-- ============================================================================

INSERT INTO dossier_medical (date_creation, allergies, maladies_chroniques, patient_id) VALUES
(NOW(), 'Pénicilline, Pollen', 'Hypertension', 4),
(NOW(), 'Aucune', 'Diabète Type 2', 5),
(NOW(), 'Arachides, Lactose', 'Asthme', 6);

-- ============================================================================
-- DONNÉES: Planning Médecins
-- ============================================================================

INSERT INTO planning_medecin (heure_debut_matin, heure_fin_matin, heure_debut_apres_midi, heure_fin_apres_midi, duree_consultation, jours_ouverture, medecin_id) VALUES
('08:00:00', '12:00:00', '14:00:00', '18:00:00', 30, '["lundi", "mardi", "mercredi", "jeudi", "vendredi"]', 2),
('09:00:00', '13:00:00', '15:00:00', '19:00:00', 30, '["lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"]', 3),
('08:30:00', '12:30:00', '14:30:00', '17:30:00', 45, '["lundi", "mercredi", "vendredi"]', 4);

-- ============================================================================
-- DONNÉES: Rendez-vous
-- ============================================================================

INSERT INTO rendez_vous (date_debut, date_fin, statut, note, patient_id, medecin_id) VALUES
('2026-05-10 09:00:00', '2026-05-10 09:30:00', 'CONFIRME', 'Consultation de routine', 4, 2),
('2026-05-10 10:00:00', '2026-05-10 10:30:00', 'CONFIRME', 'Suivi diabète', 5, 3),
('2026-05-11 14:00:00', '2026-05-11 14:45:00', 'EN_ATTENTE', 'Problème de peau', 6, 4);

-- ============================================================================
-- DONNÉES: Catégories de Produits
-- ============================================================================

INSERT INTO categorie_produit (nom, description, image) VALUES
('Médicaments', 'Médicaments en vente libre et sur ordonnance', 'medicaments.jpg'),
('Matériel Médical', 'Équipements et dispositifs médicaux', 'materiel.jpg'),
('Hygiène', 'Produits d\'hygiène et de soins personnels', 'hygiene.jpg'),
('Vitamines & Compléments', 'Suppléments nutritionnels et vitamines', 'vitamines.jpg');

-- ============================================================================
-- DONNÉES: Produits
-- ============================================================================

INSERT INTO produit (nom, description, prix, stock, image, categorie_id) VALUES
('Paracétamol 500mg', 'Boîte de 20 comprimés pour douleurs et fièvre', 5.50, 100, 'paracetamol.jpg', 1),
('Thermomètre Digital', 'Thermomètre médical précis et rapide', 15.00, 50, 'thermometre.jpg', 2),
('Masques Chirurgicaux', 'Boîte de 50 masques jetables', 12.00, 200, 'masques.jpg', 3),
('Vitamine C 1000mg', 'Boîte de 30 comprimés effervescents', 8.50, 75, 'vitamine-c.jpg', 4),
('Tensiomètre', 'Appareil de mesure de tension artérielle', 45.00, 30, 'tensiometre.jpg', 2),
('Gel Hydroalcoolique 500ml', 'Solution désinfectante pour les mains', 6.00, 150, 'gel.jpg', 3);

-- ============================================================================
-- DONNÉES: Codes Promo
-- ============================================================================

INSERT INTO promo_code (code, rate, start_at, end_at, usage_limit, used_count, active, created_at) VALUES
('WELCOME10', 10.00, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 100, 0, 1, NOW()),
('SUMMER20', 20.00, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), 50, 0, 1, NOW()),
('HEALTH15', 15.00, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), NULL, 0, 1, NOW());

-- ============================================================================
-- DONNÉES: Commandes
-- ============================================================================

INSERT INTO commande_produit (date_commande, statut, montant_total, adresse_livraison, telephone, pays, mode_paiement, delivery_city, utilisateur_id) VALUES
(NOW(), 'EN_ATTENTE', 33.50, 'Ariana, Tunisie', '+216 98 123 456', 'Tunisie', 'CARTE_BANCAIRE', 'Ariana', 4),
(DATE_SUB(NOW(), INTERVAL 2 DAY), 'LIVREE', 51.00, 'La Marsa, Tunisie', '+216 98 234 567', 'Tunisie', 'STRIPE', 'Tunis', 5);

-- ============================================================================
-- DONNÉES: Lignes de Commande
-- ============================================================================

INSERT INTO ligne_commande (quantite, prix_unitaire, commande_id, produit_id) VALUES
(2, 5.50, 1, 1),
(1, 15.00, 1, 2),
(1, 45.00, 2, 5),
(1, 6.00, 2, 6);

-- ============================================================================
-- DONNÉES: Catégories Santé
-- ============================================================================

INSERT INTO categorie_sante (id, nom, description, type, statut, date_approbation, cree_par_id, approuve_par_id) VALUES
(UUID_TO_BIN(UUID()), 'Cardiologie', 'Santé cardiovasculaire et prévention', 'MEDICAL', 'APPROUVE', NOW(), 2, 1),
(UUID_TO_BIN(UUID()), 'Nutrition', 'Alimentation saine et équilibrée', 'PREVENTION', 'APPROUVE', NOW(), 3, 1),
(UUID_TO_BIN(UUID()), 'Diabète', 'Gestion et prévention du diabète', 'MEDICAL', 'APPROUVE', NOW(), 2, 1);

-- ============================================================================
-- DONNÉES: Événements
-- ============================================================================

INSERT INTO evenement (id, title, content, is_active, statut, approuve_at, event_date, location, event_time, max_participants, type_evenement, created_at, organisateur_id, approuve_par_id) VALUES
(UUID(), 'Conférence sur le Diabète', 'Conférence médicale sur la prévention et le traitement du diabète', 1, 'APPROUVE', NOW(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'Hôtel Sheraton, Tunis', '14:00', 100, 'CONFERENCE', NOW(), 2, 1),
(UUID(), 'Atelier Nutrition Enfants', 'Atelier pratique sur la nutrition des enfants', 1, 'APPROUVE', NOW(), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'Centre Médical, Ariana', '10:00', 30, 'ATELIER', NOW(), 3, 1);

-- ============================================================================
-- DONNÉES: Notifications
-- ============================================================================

INSERT INTO notification (titre, message, type, lien, est_lu, date_creation, utilisateur_id) VALUES
('Rendez-vous confirmé', 'Votre rendez-vous du 10/05/2026 à 09:00 est confirmé', 'INFO', '/rendez-vous/1', 0, NOW(), 4),
('Nouvelle commande', 'Votre commande #1 a été enregistrée avec succès', 'SUCCESS', '/commandes/1', 0, NOW(), 4),
('Rappel rendez-vous', 'Rappel: Rendez-vous demain à 10:00', 'WARNING', '/rendez-vous/2', 0, NOW(), 5);

-- ============================================================================
-- FIN DES DONNÉES D'EXEMPLE
-- ============================================================================

-- Note: Les mots de passe doivent être hashés avec password_hash() en PHP
-- Exemple: password_hash('password123', PASSWORD_BCRYPT)
