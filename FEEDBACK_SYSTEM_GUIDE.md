# 🌟 Système de Feedback pour les Événements

## ✅ Implémentation Terminée

Un système complet de feedback a été ajouté pour permettre aux participants de noter les événements avec des étoiles (1-5) et laisser des commentaires.

## 🎯 Fonctionnalités

### Pour les Participants

✅ **Noter un événement** : 1 à 5 étoiles  
✅ **Laisser un commentaire** : Jusqu'à 1000 caractères  
✅ **Modifier son feedback** : Possibilité de mettre à jour  
✅ **Voir tous les feedbacks** : Page dédiée avec statistiques

### Pour les Organisateurs

✅ **Voir la note moyenne** : Affichée sur la page de l'événement  
✅ **Consulter tous les feedbacks** : Liste complète avec commentaires  
✅ **Statistiques détaillées** : Distribution des notes, nombre total d'avis

## 📁 Fichiers Créés

### Entités et Repositories

1. **`src/Entity/EventFeedback.php`**
   - Entité pour stocker les feedbacks
   - Champs : rating (1-5), comment, participant, evenement
   - Contrainte unique : un feedback par participant par événement

2. **`src/Repository/EventFeedbackRepository.php`**
   - Méthodes pour récupérer les feedbacks
   - Calcul de la note moyenne
   - Distribution des notes
   - Comptage des feedbacks

### Formulaires

3. **`src/Form/EventFeedbackFormType.php`**
   - Formulaire de feedback
   - Sélection de la note (1-5 étoiles)
   - Zone de commentaire (optionnel, max 1000 caractères)

### Templates

4. **`templates/evenement/feedback.html.twig`**
   - Page pour laisser/modifier un feedback
   - Design moderne avec dégradé violet/bleu
   - Conseils pour un bon feedback

5. **`templates/evenement/feedbacks.html.twig`**
   - Page listant tous les feedbacks
   - Statistiques : note moyenne, distribution
   - Liste des commentaires avec notes

### Contrôleur

6. **`src/Controller/EvenementController.php`** (modifié)
   - Ajout de `EventFeedbackRepository` dans le constructeur
   - Méthode `leaveFeedback()` : Laisser/modifier un feedback
   - Méthode `viewFeedbacks()` : Voir tous les feedbacks
   - Méthode `show()` : Affiche les statistiques de feedback

### Templates Modifiés

7. **`templates/evenement/show.html.twig`** (modifié)
   - Section "Feedbacks des participants"
   - Affichage de la note moyenne avec étoiles
   - Bouton "Laisser un feedback" (si événement terminé)
   - Bouton "Voir tous les feedbacks"
   - Modal pour demander l'email du participant

## 🚀 Utilisation

### Laisser un Feedback

1. **Aller sur la page d'un événement terminé**
   - URL : `/evenement/{id}`

2. **Cliquer sur "Laisser un feedback"**
   - Un modal s'ouvre pour demander votre email

3. **Entrer l'email utilisé lors de l'inscription**
   - Seuls les participants inscrits peuvent laisser un feedback

4. **Remplir le formulaire**
   - Choisir une note de 1 à 5 étoiles
   - Laisser un commentaire (optionnel)

5. **Soumettre**
   - Le feedback est enregistré
   - Possibilité de le modifier ultérieurement

### Voir les Feedbacks

1. **Sur la page de l'événement**
   - La note moyenne est affichée avec des étoiles
   - Le nombre total de feedbacks est indiqué

2. **Cliquer sur "Voir tous les feedbacks"**
   - Page dédiée avec :
     - Note moyenne globale
     - Distribution des notes (graphique)
     - Liste de tous les commentaires

## 📊 Statistiques Affichées

### Note Moyenne
- Calculée automatiquement
- Affichée avec des étoiles (ex: 4.5/5)
- Mise à jour en temps réel

### Distribution des Notes
- Graphique en barres
- Nombre de feedbacks par note (5★, 4★, 3★, 2★, 1★)
- Pourcentage visuel

### Liste des Feedbacks
- Nom du participant
- Note avec étoiles
- Commentaire
- Date de création
- Badge "Modifié" si mis à jour

## 🔒 Sécurité et Validation

### Contraintes

✅ **Un feedback par participant** : Contrainte unique en base de données  
✅ **Événement terminé** : Feedback possible uniquement après la date de l'événement  
✅ **Participant inscrit** : Vérification de l'inscription à l'événement  
✅ **Note obligatoire** : Entre 1 et 5 étoiles  
✅ **Commentaire optionnel** : Maximum 1000 caractères

### Validation

- Note : Entre 1 et 5 (validation Symfony)
- Commentaire : Maximum 1000 caractères
- Email : Format email valide
- Participant : Doit être inscrit à l'événement

## 🎨 Design

### Couleurs

- **Dégradé principal** : Violet (#667eea) → Mauve (#764ba2)
- **Étoiles** : Jaune (#ffc107)
- **Cartes** : Blanc avec ombre légère
- **Boutons** : Dégradé avec effet hover

### Icônes Bootstrap

- `bi-star-fill` : Étoile pleine
- `bi-star` : Étoile vide
- `bi-chat-left-text` : Commentaires
- `bi-person-circle` : Participant
- `bi-calendar-event` : Date

## 📝 Exemples de Requêtes

### Laisser un Feedback

```
GET /evenement/{id}/feedback?email=participant@example.com
POST /evenement/{id}/feedback
```

### Voir les Feedbacks

```
GET /evenement/{id}/feedbacks
```

## 🧪 Tests

### Test 1 : Laisser un Feedback

1. Créer un événement passé
2. S'inscrire à l'événement avec un email
3. Aller sur la page de l'événement
4. Cliquer sur "Laisser un feedback"
5. Entrer l'email d'inscription
6. Remplir le formulaire (note + commentaire)
7. Soumettre
8. Vérifier que le feedback apparaît

### Test 2 : Modifier un Feedback

1. Laisser un feedback (voir Test 1)
2. Retourner sur la page de feedback avec le même email
3. Le formulaire est pré-rempli
4. Modifier la note ou le commentaire
5. Soumettre
6. Vérifier que le feedback est mis à jour
7. Badge "Modifié" doit apparaître

### Test 3 : Voir les Statistiques

1. Créer plusieurs feedbacks avec des notes différentes
2. Aller sur la page de l'événement
3. Vérifier la note moyenne affichée
4. Cliquer sur "Voir tous les feedbacks"
5. Vérifier :
   - Note moyenne correcte
   - Distribution des notes
   - Liste des commentaires

### Test 4 : Restrictions

1. **Événement futur** : Le bouton "Laisser un feedback" ne doit pas être disponible
2. **Non-participant** : Erreur si l'email n'est pas inscrit
3. **Doublon** : Impossible de créer deux feedbacks (redirection vers modification)

## 🔧 Base de Données

### Table `event_feedback`

```sql
CREATE TABLE event_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT NOT NULL,
    evenement_id CHAR(36) NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY unique_participant_feedback (participant_id),
    FOREIGN KEY (participant_id) REFERENCES participant(id) ON DELETE CASCADE,
    FOREIGN KEY (evenement_id) REFERENCES evenement(id) ON DELETE CASCADE
);
```

## 💡 Améliorations Futures

### Court Terme

- [ ] Ajouter des réactions rapides (👍 👎 ❤️)
- [ ] Permettre de répondre aux feedbacks
- [ ] Notification email à l'organisateur

### Moyen Terme

- [ ] Analyse de sentiment des commentaires
- [ ] Graphiques avancés (évolution dans le temps)
- [ ] Export des feedbacks en PDF/Excel

### Long Terme

- [ ] IA pour détecter les feedbacks inappropriés
- [ ] Recommandations basées sur les feedbacks
- [ ] Badges pour les meilleurs événements

## 📞 Support

### Problèmes Courants

**Erreur : "Vous devez avoir participé à cet événement"**
- Vérifier que l'email est correct
- Vérifier que vous êtes bien inscrit à l'événement

**Le bouton "Laisser un feedback" n'apparaît pas**
- L'événement doit être terminé (date passée)
- Vérifier la date de l'événement

**Erreur : "Événement non publié"**
- L'événement doit être validé par un admin
- Statut doit être "VALIDE"

## 🎉 Résumé

✅ **Système complet de feedback opérationnel**
- Notes de 1 à 5 étoiles
- Commentaires optionnels
- Statistiques détaillées
- Design moderne et responsive
- Sécurité et validation robustes

Le système est prêt à l'emploi et peut être testé immédiatement!
