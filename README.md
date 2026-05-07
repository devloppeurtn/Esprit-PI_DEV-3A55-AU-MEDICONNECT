# MediConnect – Smart Healthcare Platform

## Overview
MediConnect est une plateforme web de gestion médicale développée dans le cadre du module **PIDEV – 3ème année ingénierie** à **Esprit School of Engineering** (Année universitaire 2025–2026).  
L’application permet de gérer les patients, les dossiers médicaux, les consultations, les rendez-vous, les ordonnances, ainsi qu’un module e-commerce pour les produits de santé, avec des fonctionnalités avancées d’analytics, de notifications et d’authentification forte (2FA, biométrie).

## Features
- **Gestion des utilisateurs**: rôles (patient, médecin, admin, secrétaire), profils, statut de compte, photo de profil.
- **Dossiers médicaux & consultations**: création et suivi des dossiers, consultations, ordonnances, documents patients.
- **Rendez-vous & calendrier**: prise de rendez-vous, suivi des disponibilités, invitations entre médecins et secrétaires.
- **Événements & participation**: gestion d’événements santé, inscriptions des participants, validations médecins.
- **Cours éducatifs & progression**: cours éducatifs par catégories de santé, quiz, progression des utilisateurs.
- **E-commerce santé**: gestion des produits, panier, commandes, prix dynamiques, recommandations produits.
- **Notifications in-app**: notifications patients (nouvelle consultation, rendez-vous, ordonnance, etc.).
- **Authentification avancée**:
  - Login classique avec mot de passe haché
  - Connexion via **Google OAuth2**
  - **2FA** (Google Authenticator) via Scheb TwoFactorBundle
  - Support de la **biométrie/FaceID** (stockage d’embeddings faciaux en JSON)
- **Analytics & automatisation**:
  - Commandes Symfony de recalcul de prix, analytics, alertes, entraînement modèle de stock
  - Intégration Stripe (webhooks) pour les paiements

## Tech Stack

### Frontend
- **Twig** (moteur de templates Symfony)
- **HTML5 / CSS3 / JavaScript**
- **Bootstrap 5** (via assets vendor)
- **Font Awesome** & **Bootstrap Icons**
- **AOS**, **Swiper**, **Glightbox**, **PureCounter** (pour l’UI/UX et les animations)
- **Symfony UX** (Turbo, Stimulus, Chart.js, Autocomplete)

### Backend
- **PHP 8.x**
- **Symfony** (framework principal)
- **Doctrine ORM** (mapping objet–relationnel)
- **MySQL / MariaDB** (base de données `mediconnect`)
- **Symfony Security** (auth, rôles, firewalls)
- **Scheb TwoFactorBundle** (2FA)
- **KnpU OAuth2 Client** (Google OAuth2)
- **Stripe** (paiements & webhooks)
- **PHPUnit / PHPStan** (tests et qualité de code)

## Architecture
- **Couche présentation**: vues Twig dans `templates/` (authentification, profil, admin, événements, e-commerce, etc.).
- **Couche contrôleurs**: contrôleurs Symfony dans `src/Controller/` (auth, utilisateur, commande, produit, événement, notifications, etc.).
- **Couche domaine / persistance**:
  - Entités Doctrine dans `src/Entity/` (Utilisateur, Consultation, DossierMedical, Produit, Notification, Evenement, etc.).
  - Repositories dans `src/Repository/`.
- **Couche services métier**:
  - `CartService`, `OrderAnalyticsService`, `ProductPricingService`, `ProductRecommendationService`, services de règles métier (événements, produits, progression, rendez-vous, utilisateurs).
  - `AwsFaceIdService` pour la biométrie.
- **Infrastructure**:
  - Migrations Doctrine dans `migrations/`.
  - Configuration Symfony dans `config/`.
  - Assets compilés dans `public/assets/`.
- **Tâches planifiées / batch**:
  - Commandes Symfony dans `src/Command/` pour les recalculs, entraînement modèle de stock, vérification des webhooks, envoi d’alertes.

## Contributors
- **[Ton Nom]** – Développeur principal (3A – Esprit School of Engineering)  
- Encadrant académique : **[Nom de l’enseignant / encadrant]**  
*(Ajoute ici les autres membres de l’équipe si projet en groupe.)*

## Academic Context
Ce projet a été réalisé dans le cadre du module **PIDEV – Projet Intégré de Développement** en **3ème année** à  
**Esprit School of Engineering – Tunisie**, Année universitaire **2025–2026**.

L’objectif pédagogique est de :
- Concevoir et implémenter une application web full-stack complète.
- Mettre en pratique les concepts de génie logiciel (architecture, couches, bonnes pratiques).
- Intégrer des services externes (paiement, OAuth2, 2FA, biométrie).
- Assurer la qualité via tests, outils d’analyse statique et migrations structurées.

## Getting Started

### Prérequis
- **PHP 8.x**
- **Composer**
- **MySQL / MariaDB**
- **Node.js / npm** (si tu recompiles des assets)
- Extensions PHP nécessaires (pdo_mysql, intl, etc.)

### Installation

1. **Cloner le projet**
   ```bash
   git clone <url-du-repo>
   cd mediconnect