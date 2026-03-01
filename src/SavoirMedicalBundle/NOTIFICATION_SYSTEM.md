# Système de Notifications en Temps Réel

## Vue d'ensemble
Le système de notifications améliore la communication, la réactivité et l'engagement des utilisateurs au sein de la plateforme MediConnect. Il utilise Mercure pour les notifications en temps réel et une base de données pour la persistance.

## Architecture

### Technologies utilisées
- **Symfony Messenger**: Gestion asynchrone des notifications
- **Mercure**: Push notifications en temps réel via Server-Sent Events (SSE)
- **Doctrine ORM**: Persistance des notifications en base de données
- **JavaScript EventSource API**: Réception des notifications côté client

### Composants

#### 1. Entity: Notification
Localisation: `src/Entity/Notification.php`

Propriétés:
- `id` (UUID): Identifiant unique
- `destinataire` (Utilisateur): Utilisateur qui reçoit la notification
- `type` (string): Type de notification (CATEGORIE_EN_ATTENTE, CATEGORIE_APPROUVEE, etc.)
- `titre` (string): Titre court de la notification
- `message` (text): Message détaillé
- `lien` (string): URL vers la ressource concernée
- `lu` (boolean): Statut de lecture
- `dateCreation` (DateTimeImmutable): Date de création
- `dateLecture` (DateTimeImmutable): Date de lecture
- `categorieId` (UUID): Référence à la catégorie concernée

#### 2. Service: NotificationService
Localisation: `src/Service/NotificationService.php`

Méthodes principales:
- `notifierAdminNouvelleCategorie()`: Notifie les admins d'une nouvelle catégorie
- `notifierMedecinCategorieApprouvee()`: Notifie le médecin de l'approbation
- `notifierMedecinCategorieRejetee()`: Notifie le médecin du rejet
- `notifierPatientsNouvelleCategorie()`: Notifie tous les patients d'une nouvelle catégorie
- `envoyerNotificationTempsReel()`: Envoie via Mercure
- `getNotificationsNonLues()`: Récupère les notifications non lues
- `marquerCommeLu()`: Marque une notification comme lue

#### 3. Controller: NotificationController
Localisation: `src/Controller/NotificationController.php`

Routes:
- `GET /notifications/api/list`: Liste des notifications (JSON)
- `POST /notifications/api/{id}/mark-read`: Marquer comme lu
- `POST /notifications/api/mark-all-read`: Tout marquer comme lu
- `GET /notifications/api/unread-count`: Nombre de non lues
- `GET /notifications/`: Page de toutes les notifications

## Workflow des Notifications

### 1. Médecin crée une catégorie
```
Médecin → Crée catégorie (statut: EN_ATTENTE)
    ↓
NotificationService.notifierAdminNouvelleCategorie()
    ↓
Notification créée en DB pour chaque Admin
    ↓
Mercure envoie notification temps réel
    ↓
Admin reçoit notification instantanément
```

### 2. Admin approuve la catégorie
```
Admin → Approuve catégorie (statut: APPROUVE)
    ↓
NotificationService.notifierMedecinCategorieApprouvee()
    ↓
Notification créée pour le Médecin
    ↓
NotificationService.notifierPatientsNouvelleCategorie()
    ↓
Notifications créées pour tous les Patients
    ↓
Mercure envoie notifications temps réel
    ↓
Médecin et Patients reçoivent notifications
```

### 3. Admin rejette la catégorie
```
Admin → Rejette catégorie (statut: REJETE)
    ↓
NotificationService.notifierMedecinCategorieRejetee()
    ↓
Notification créée pour le Médecin
    ↓
Mercure envoie notification temps réel
    ↓
Médecin reçoit notification de rejet
```

## Types de Notifications

### CATEGORIE_EN_ATTENTE
- **Destinataire**: Administrateurs
- **Déclencheur**: Médecin crée une nouvelle catégorie
- **Message**: "Le Dr. [Nom] a créé une nouvelle catégorie '[Nom]' qui nécessite votre validation."
- **Action**: Lien vers la page des catégories en attente

### CATEGORIE_APPROUVEE
- **Destinataire**: Médecin créateur
- **Déclencheur**: Admin approuve la catégorie
- **Message**: "Votre catégorie '[Nom]' a été approuvée par [Admin] et est maintenant visible par les patients."
- **Action**: Lien vers la catégorie

### CATEGORIE_REJETEE
- **Destinataire**: Médecin créateur
- **Déclencheur**: Admin rejette la catégorie
- **Message**: "Votre catégorie '[Nom]' a été rejetée par [Admin]. Veuillez la réviser et la soumettre à nouveau."
- **Action**: Lien vers la page Savoir Médical

### NOUVELLE_CATEGORIE
- **Destinataire**: Tous les patients
- **Déclencheur**: Catégorie approuvée et publiée
- **Message**: "Une nouvelle catégorie '[Nom]' ([Type]) est maintenant disponible. Découvrez de nouveaux cours et quiz !"
- **Action**: Lien vers la catégorie

## Intégration Frontend

### Widget de notification (Cloche)
Localisation: `templates/notifications/_notification_bell.html.twig`

Fonctionnalités:
- Badge avec compteur de notifications non lues
- Dropdown avec les 10 dernières notifications
- Mise à jour en temps réel via Mercure
- Fallback avec polling toutes les 30 secondes
- Notifications navigateur (si autorisées)

### Intégration dans la navigation
Ajoutez dans votre template de base:

```twig
{# Dans la barre de navigation #}
{% include 'notifications/_notification_bell.html.twig' %}
```

### Page complète des notifications
Route: `/notifications/`
Template: `templates/notifications/index.html.twig`

Fonctionnalités:
- Liste complète des notifications (50 dernières)
- Filtrage visuel (lues/non lues)
- Bouton "Tout marquer comme lu"
- Navigation vers les ressources concernées

## Configuration Mercure

### Variables d'environnement (.env)
```env
MERCURE_URL=http://127.0.0.1:1337/.well-known/mercure
MERCURE_PUBLIC_URL=http://127.0.0.1:1337/.well-known/mercure
MERCURE_JWT_SECRET="!ChangeThisMercureHubJWTSecretKey!"
```

### Topics Mercure
Format: `notifications/{userId}`

Exemple: `notifications/01234567-89ab-cdef-0123-456789abcdef`

### Démarrage du hub Mercure
```bash
# Avec Docker (recommandé)
docker-compose up -d

# Ou standalone
./mercure run --config Caddyfile
```

## API Endpoints

### GET /notifications/api/list
Récupère les notifications de l'utilisateur connecté

**Réponse:**
```json
{
  "notifications": [
    {
      "id": "uuid",
      "type": "CATEGORIE_APPROUVEE",
      "titre": "Catégorie approuvée",
      "message": "Votre catégorie...",
      "lien": "/savoir-medical/categorie/uuid",
      "lu": false,
      "dateCreation": "2026-02-25T10:30:00+00:00"
    }
  ],
  "unreadCount": 3
}
```

### POST /notifications/api/{id}/mark-read
Marque une notification comme lue

**Réponse:**
```json
{
  "success": true
}
```

### POST /notifications/api/mark-all-read
Marque toutes les notifications comme lues

**Réponse:**
```json
{
  "success": true
}
```

### GET /notifications/api/unread-count
Compte les notifications non lues

**Réponse:**
```json
{
  "count": 5
}
```

## Performance et Optimisation

### Batch Notifications
Pour les notifications aux patients (potentiellement nombreux):
- Notifications créées en batch en base de données
- Mercure temps réel désactivé si > 100 destinataires
- Les patients récupèrent via polling ou au prochain chargement

### Indexation Base de Données
Index recommandés:
```sql
CREATE INDEX idx_notification_destinataire_lu ON notification(destinataire_id, lu);
CREATE INDEX idx_notification_date ON notification(date_creation DESC);
```

### Nettoyage Automatique
Recommandation: Supprimer les notifications lues de plus de 30 jours

```php
// Commande Symfony à créer
php bin/console app:notifications:cleanup
```

## Sécurité

### Vérifications
- Seul le destinataire peut voir/marquer ses notifications
- Authentification requise pour tous les endpoints
- Validation des UUID pour éviter les injections

### Mercure JWT
Le JWT Mercure doit être signé avec le secret configuré:
```php
$token = (new Builder())
    ->withClaim('mercure', ['subscribe' => ["notifications/{$userId}"]])
    ->getToken(new Sha256(), new Key($mercureSecret));
```

## Tests

### Test Unitaire du Service
```php
public function testNotifierAdminNouvelleCategorie(): void
{
    $categorie = new CategorieSante();
    $categorie->setNom('Test');
    
    $medecin = new Medecin();
    
    $this->notificationService->notifierAdminNouvelleCategorie($categorie, $medecin);
    
    // Vérifier que les admins ont reçu la notification
    $notifications = $this->notificationRepository->findAll();
    $this->assertCount(1, $notifications);
    $this->assertEquals('CATEGORIE_EN_ATTENTE', $notifications[0]->getType());
}
```

### Test d'Intégration Mercure
```javascript
// Test EventSource connection
const eventSource = new EventSource('http://127.0.0.1:1337/.well-known/mercure?topic=notifications/test-user-id');

eventSource.onmessage = (event) => {
    console.log('Notification received:', JSON.parse(event.data));
};
```

## Monitoring

### Métriques à surveiller
- Nombre de notifications créées par jour
- Taux de lecture des notifications
- Temps de réponse des endpoints API
- Connexions Mercure actives
- Erreurs de publication Mercure

### Logs
```php
// Dans NotificationService
$this->logger->info('Notification sent', [
    'type' => $notification->getType(),
    'recipient' => $notification->getDestinataire()->getId(),
    'mercure_success' => $mercurePublished
]);
```

## Évolutions Futures

### Fonctionnalités à ajouter
1. **Préférences de notification**: Permettre aux utilisateurs de choisir quels types recevoir
2. **Notifications par email**: Envoyer aussi par email pour les notifications importantes
3. **Notifications SMS**: Pour les urgences médicales
4. **Groupement**: Regrouper les notifications similaires
5. **Actions rapides**: Approuver/rejeter directement depuis la notification
6. **Historique**: Archivage des anciennes notifications

### Améliorations techniques
1. **Queue asynchrone**: Utiliser Symfony Messenger pour les notifications batch
2. **Cache**: Mettre en cache le compteur de notifications non lues
3. **WebSocket**: Alternative à Mercure pour certains cas d'usage
4. **Push mobile**: Intégration avec Firebase Cloud Messaging

## Support
Pour toute question sur le système de notifications:
- Documentation Mercure: https://mercure.rocks/docs
- Documentation Symfony Messenger: https://symfony.com/doc/current/messenger.html
- Support MediConnect: contact@mediconnect.fr
