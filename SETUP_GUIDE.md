# Guide d'Installation - Nouvelles Fonctionnalités MediConnect

## 📋 Prérequis
- Symfony 6.x avec Doctrine ORM
- Base de données MySQL/MariaDB
- PHP 8.1+

## 🔧 Étapes d'Installation

### 1. Déployer les Migrations
```bash
# Exécuter les migrations Doctrine
php bin/console doctrine:migrations:migrate

# Ou si vous préférez voir les migrations en attente
php bin/console doctrine:migrations:status
```

Cela créera:
- La colonne `max_participants` dans la table `evenement`
- La nouvelle table `avis_medecin` avec les relations

### 2. Vérifier les Entités
```bash
# Valider que les entités sont bien formées
php bin/console doctrine:schema:validate
```

### 3. Tester les Nouvelles Fonctionnalités

#### Tester la limite de participants:
1. Se connecter comme Organisateur
2. Créer un nouvel événement
3. Fixer "Nombre maximal de participants" à par exemple 2
4. Essayer d'ajouter 3 participants - la 3ème inscription doit être refusée

#### Tester le système d'évaluation:
1. Se connecter comme Patient
2. Aller dans "Mes avis" depuis le dashboard
3. Cliquer sur "Évaluer un médecin" ou depuis la page des médecins
4. Sélectionner une note et ajouter un commentaire
5. Soumettre le formulaire
6. Voir l'avis dans "Mes avis"

---

## 📂 Structure des Fichiers

### Entités
```
src/Entity/
├── AvisMedecin.php (NOUVEAU)
└── Medecin.php (MODIFIÉ - relation OneToMany ajoutée)
```

### Repository
```
src/Repository/
└── AvisMedecinRepository.php (NOUVEAU)
```

### Formulaires
```
src/Form/
└── AvisMedecinFormType.php (NOUVEAU)
```

### Contrôleurs
```
src/Controller/
├── EvenementController.php (MODIFIÉ - validation limite participants)
└── PatientController.php (MODIFIÉ - nouvelles routes d'évaluation)
```

### Templates
```
templates/patient/
├── rate_medecin.html.twig (NOUVEAU)
├── my_ratings.html.twig (NOUVEAU)
└── index.html.twig (MODIFIÉ - ajout lien "Mes avis")
```

### Migrations
```
migrations/
├── Version20260224140000.php (NOUVEAU - max_participants)
└── Version20260224150000.php (NOUVEAU - table avis_medecin)
```

---

## 🧪 Tests API

### Créer une évaluation (POST)
```bash
curl -X POST http://localhost:8000/patient/avis-medecin/MEDECIN_ID \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "avis_medecin_form_type[note]=5&avis_medecin_form_type[commentaire]=Excellent+medecin"
```

### Récupérer mes avis (GET)
```bash
curl http://localhost:8000/patient/mes-avis
```

---

## ⚠️ Points Importants

### Sécurité
- Seuls les patients authentifiés (ROLE_PATIENT) peuvent évaluer les médecins
- Un patient ne peut avoir qu'un seul avis par médecin (UNIQUE constraint)
- Les données sont cascade-supprimées si le médecin ou patient est supprimé

### Performance
- L'index sur (medecin_id, patient_id) dans `avis_medecin` optimise les recherches
- Le champ `maxParticipants` est nullable pour maintenir la compatibilité

### Validation
- La note (stars) est obligatoire (1-5)
- Le commentaire est optionnel (max 1000 caractères)
- La limite de participants doit être >= 1 (si définie)

---

## 🐛 Dépannage

### Erreur: "UNIQUE constraint failed"
- Un patient a déjà un avis pour ce médecin
- Solution: Modifier l'avis existant ou le supprimer d'abord

### Migration échoue
```bash
# Voir les erreurs détaillées
php bin/console doctrine:migrations:migrate --verbose

# Revenir à la dernière version stable
php bin/console doctrine:migrations:migrate --previous
```

### Colonne max_participants non trouvée
```bash
# Vérifier l'état des migrations
php bin/console doctrine:migrations:status

# Exécuter les migrations manquantes
php bin/console doctrine:migrations:migrate
```

---

## 📚 Documentation Complète

Voir [IMPLEMENTATION_FEATURES.md](IMPLEMENTATION_FEATURES.md) pour:
- Liste détaillée de tous les fichiers créés/modifiés
- Structure complète des entités
- Routes et endpoints disponibles
- Exemples d'utilisation

---

## ✅ Checklist Post-Installation

- [ ] Migrations exécutées avec succès
- [ ] Aucune erreur dans `doctrine:schema:validate`
- [ ] Les routes patient sont accessibles: `/patient/mes-avis` et `/patient/avis-medecin/{id}`
- [ ] Le formulaire d'événement affiche le champ "Nombre maximal de participants"
- [ ] Un test d'inscription avec limite fonctionne
- [ ] Un test d'évaluation fonctionne

---

Pour toute question ou problème, consultez la documentation ou les commentaires du code.
