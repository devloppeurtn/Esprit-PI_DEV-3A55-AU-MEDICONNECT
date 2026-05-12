# Sprint 1 — Module 1 (1 semaine) — Répartition Trello

**Période :** 1 semaine  
**Module :** Module 1 (Gestionnaire Users, Admin, Authentification, Face ID)  
**Responsable :** Houssem Hfaissi  

Utilisez les listes : **À faire** | **En cours** | **À vérifier** | **Terminé**.  
Copiez-collez les cartes ci-dessous dans votre tableau Trello. Les tâches passent de « En cours » à « À vérifier » avant « Terminé ».

---

## Terminé (déjà livré — à mettre dans la liste « Terminé »)

| Carte à créer |
|---------------|
| **US-1 à US-3** — Gestion utilisateurs : entité, CRUD, rôles, statuts, rapports, export CSV |
| **US-4** — Authentification : login, security.yaml, UserProvider, UserChecker, logout, protection routes |
| **US-5** — Backoffice admin : dashboard, utilisateurs, événements, produits, catégories, promos, RDV, « Mes commandes » |
| **T-6.1** — WebAuthn : configuration bundle (webauthn.yaml, routes) |

---

## En cours — Détail des cartes (à copier dans la description Trello ou en checklist)

Quand une carte est dans « En cours », utilisez le détail ci-dessous pour la carte concernée (sous-tâches, critères, étapes).

---

### T-6.2 — Entités et repositories WebAuthn (remplacer les Dummy) — 1h30

**Objectif :** Avoir en base les credentials WebAuthn liés aux utilisateurs (plus de Dummy).

**Sous-tâches / Checklist :**
- [ ] Créer ou adapter l’entité (ex. `WebAuthnCredential` ou équivalent du bundle) : `userHandle`, `credentialId`, `publicKey`, `signCount`, `aaguid`, `transports`, `createdAt`, relation ManyToOne vers `User`/`Utilisateur`.
- [ ] Créer la migration Doctrine et l’exécuter (`php bin/console doctrine:migrations:migrate`).
- [ ] Créer le repository (ex. `WebAuthnCredentialRepository`) avec méthodes utiles : `findByUser(User $user)`, `findByCredentialId(string $id)`, `removeByUser(User $user)`.
- [ ] Remplacer les Dummy / stubs dans le code du bundle par les vrais appels au repository (enregistrement après création de credential, lecture pour l’authentification).
- [ ] Vérifier que le bundle WebAuthn utilise bien ce repository dans sa config (webauthn.yaml) si besoin.

**Critères d’acceptation :**
- Les credentials sont persistés en BDD et liés à un utilisateur.
- Aucun Dummy / stub ne reste pour la gestion des credentials.
- Les méthodes du repository sont utilisées par le flux d’enregistrement et de login WebAuthn.

---

### T-6.3 — Enregistrement credential Face ID depuis les paramètres utilisateur — 2h

**Objectif :** L’utilisateur peut enregistrer un appareil (Face ID / clé de sécurité) depuis sa page paramètres.

**Sous-tâches / Checklist :**
- [ ] Créer une route (ex. `/parametres/webauthn/register` ou équivalent du bundle) réservée aux utilisateurs connectés.
- [ ] Page ou section « Sécurité » / « Authentification » dans les paramètres avec bouton « Enregistrer cet appareil » (ou « Activer Face ID »).
- [ ] Appeler l’API WebAuthn d’enregistrement (creation options côté serveur, `navigator.credentials.create()` côté client).
- [ ] Envoyer le credential créé au serveur et le sauvegarder via le repository (lien utilisateur courant).
- [ ] Afficher un message de succès et, si possible, le nom/label de l’appareil dans la liste (T-6.5 peut venir après).

**Critères d’acceptation :**
- Un utilisateur connecté peut enregistrer un credential depuis les paramètres.
- Le credential est bien associé à son compte en BDD.
- Un retour visuel confirme la réussite (message ou redirection).

---

### T-6.4 — Option « Se connecter avec Face ID » sur l’écran de login — 1h30

**Objectif :** Sur la page de login, proposer une option pour s’authentifier via WebAuthn (Face ID / clé).

**Sous-tâches / Checklist :**
- [ ] Sur la page de login, ajouter un bouton/lien « Se connecter avec Face ID » (ou « Clé de sécurité ») visible si WebAuthn est supporté.
- [ ] Côté serveur : générer les options d’assertion (get options) pour l’utilisateur (email ou userHandle si déjà saisi).
- [ ] Côté client : appeler `navigator.credentials.get()` avec les options reçues (authenticatorSelection si besoin).
- [ ] Envoyer l’assertion au serveur ; le bundle vérifie la signature et identifie l’utilisateur.
- [ ] En cas de succès : connecter l’utilisateur (session Symfony) et rediriger vers la page d’accueil ou le tableau de bord.
- [ ] Gérer les erreurs (credential refusé, annulé, appareil non enregistré) avec un message clair.

**Critères d’acceptation :**
- L’option Face ID / WebAuthn est visible sur le login (si supporté).
- Un utilisateur ayant enregistré un appareil peut se connecter sans mot de passe via WebAuthn.
- Les erreurs sont gérées sans crash et avec un message utilisateur compréhensible.

---

### T-6.5 — Page paramètres : gérer les appareils enregistrés (liste, supprimer) — 1h

**Objectif :** Dans les paramètres, l’utilisateur voit la liste de ses appareils WebAuthn et peut en supprimer.

**Sous-tâches / Checklist :**
- [ ] Afficher la liste des credentials de l’utilisateur courant (appel au repository `findByUser()`).
- [ ] Pour chaque credential : afficher un libellé (nom appareil, date d’ajout, ou « Appareil 1 », « Appareil 2 »).
- [ ] Bouton « Supprimer » par credential : route dédiée (ex. `POST /parametres/webauthn/remove/{id}`) avec CSRF et vérification que le credential appartient à l’utilisateur connecté.
- [ ] Après suppression : retirer l’entrée en BDD et rafraîchir la liste (ou redirection avec message).

**Critères d’acceptation :**
- La liste des appareils enregistrés est visible dans les paramètres.
- La suppression ne concerne que les credentials de l’utilisateur connecté.
- La liste se met à jour après suppression.

---

### T-6.6 — Messages et fallback si WebAuthn non supporté — 45min

**Objectif :** Expérience claire quand le navigateur ou l’appareil ne supporte pas WebAuthn.

**Sous-tâches / Checklist :**
- [ ] Détecter le support WebAuthn côté client (ex. `window.PublicKeyCredential` ou équivalent).
- [ ] Si non supporté : masquer ou désactiver le bouton « Se connecter avec Face ID » et afficher un court message (ex. « Votre navigateur ne supporte pas la connexion par Face ID. Utilisez votre mot de passe. »).
- [ ] Dans la page paramètres : si WebAuthn non supporté, masquer ou désactiver la section « Enregistrer cet appareil » et afficher un message explicatif.
- [ ] Optionnel : lien « En savoir plus » vers une page d’aide ou doc sur les navigateurs compatibles.

**Critères d’acceptation :**
- Aucune erreur JavaScript ni appel WebAuthn si le navigateur ne supporte pas.
- L’utilisateur comprend qu’il peut utiliser le mot de passe à la place.
- Les messages sont en français et cohérents entre login et paramètres.

---

## Résumé « En cours » (cartes à créer dans Trello)

| Carte | Estimation | Détail ci-dessus |
|-------|------------|------------------|
| **T-6.2** — Entités et repositories WebAuthn | 1h30 | § T-6.2 |
| **T-6.3** — Enregistrement Face ID (paramètres) | 2h | § T-6.3 |
| **T-6.4** — Option Face ID sur le login | 1h30 | § T-6.4 |
| **T-6.5** — Paramètres : liste et suppression appareils | 1h | § T-6.5 |
| **T-6.6** — Messages et fallback WebAuthn | 45min | § T-6.6 |

Au début du sprint : mettre **T-6.2** en « En cours » et copier le détail du § T-6.2 dans la description ou en checklist de la carte. Ensuite faire de même pour T-6.3, T-6.4, etc.

---

## À vérifier — Deux tâches à vérifier (liste « À vérifier »)

Après développement, déplacer ces **deux cartes** en « À vérifier » et valider les points ci-dessous avant de les passer en « Terminé ».

---

### Tâche 1 à vérifier : T-6.2 — Entités et repositories WebAuthn

**Checklist de vérification :**
- [ ] En BDD : une table (ou entité) stocke bien les credentials (ex. `webauthn_credential` ou équivalent) avec au moins : `user_id`, `credential_id`, `public_key`, `created_at`.
- [ ] Aucune référence à un repository Dummy ou à un stub dans le code (recherche "Dummy", "stub", "mock" dans les fichiers WebAuthn).
- [ ] Après un enregistrement de credential (paramètres), une ligne apparaît en base pour l’utilisateur connecté.
- [ ] Les méthodes du repository (`findByUser`, `findByCredentialId`) sont bien utilisées par le bundle (appels réels, pas simulés).

**Validé par :** ________________  **Date :** ________

---

### Tâche 2 à vérifier : T-6.4 — Option « Se connecter avec Face ID » sur le login

**Checklist de vérification :**
- [ ] Sur la page de login, le bouton « Se connecter avec Face ID » (ou équivalent) est visible lorsque le navigateur supporte WebAuthn.
- [ ] Parcours complet : utilisateur enregistre un appareil (paramètres) → se déconnecte → sur le login, clique sur Face ID → s’authentifie sans mot de passe et est redirigé correctement.
- [ ] En cas d’annulation (utilisateur refuse Face ID) : message clair, pas de crash, possibilité de se connecter par mot de passe.
- [ ] En cas d’appareil non enregistré : message explicite (pas d’erreur technique brute).

**Validé par :** ________________  **Date :** ________

---

## À faire (ordre suggéré pour la semaine — liste « À faire »)

| # | Carte à créer | Estimation |
|---|----------------|------------|
| 1 | **T-6.3** — Enregistrement d’un appareil / credential Face ID depuis les paramètres utilisateur | 2h |
| 2 | **T-6.4** — Intégration WebAuthn sur l’écran de login (option « Se connecter avec Face ID ») | 1h30 |
| 3 | **T-6.5** — Page paramètres utilisateur : gérer les appareils enregistrés (liste, supprimer) | 1h |
| 4 | **T-6.6** — Messages utilisateur et fallback si le navigateur n’accepte pas WebAuthn | 45min |

**Total restant (Sprint 1) :** ~7h45 — adapté à une semaine avec d’éventuelles réunions / imprévus.

---

## Descriptions pour chaque carte (à copier dans le champ « Description » Trello)

### T-6.2 — Entité credential (champs, migration), repository, remplacement Dummy, config bundle

Créer ou adapter l'entité qui stocke les credentials WebAuthn (ex. `WebAuthnCredential`) : champs `userHandle`, `credentialId`, `publicKey`, `signCount`, `aaguid`, `transports`, `createdAt`, et relation ManyToOne vers l'utilisateur. Générer et exécuter la migration Doctrine. Créer le repository avec les méthodes `findByUser()`, `findByCredentialId()`, et éventuellement `removeByUser()`. Remplacer tous les Dummy / stubs du bundle par les vrais appels à ce repository (enregistrement et lecture des credentials). Vérifier la configuration du bundle (webauthn.yaml) pour qu'il utilise ce repository. Objectif : plus aucun credential en mémoire ou Dummy — tout est persisté en BDD et lié à un utilisateur.

---

### T-6.3 — Route paramètres, bouton « Enregistrer cet appareil », WebAuthn create, envoi au serveur, message de succès

Permettre à l'utilisateur connecté d'enregistrer son appareil (Face ID ou clé de sécurité) depuis la page paramètres. Mettre en place une section « Sécurité » ou « Authentification » avec un bouton « Enregistrer cet appareil » / « Activer Face ID ». Côté serveur : route protégée qui génère les options d'enregistrement WebAuthn. Côté client : appel à `navigator.credentials.create()`, envoi du credential au serveur, sauvegarde en BDD via le repository (lié à l'utilisateur courant). Afficher un message de succès après enregistrement.

---

### T-6.4 — Bouton Face ID sur le login, options d'assertion, credentials.get(), envoi assertion, connexion session

Ajouter sur la page de connexion un bouton « Se connecter avec Face ID » (ou « Clé de sécurité ») visible lorsque le navigateur supporte WebAuthn. Côté serveur : générer les options d'assertion (get) pour l'utilisateur (email ou userHandle). Côté client : appeler `navigator.credentials.get()`, envoyer l'assertion au serveur. Le bundle vérifie la signature, identifie l'utilisateur et ouvre la session Symfony ; redirection vers l'accueil ou le tableau de bord. Gérer les erreurs (annulation, appareil non enregistré) avec des messages clairs.

---

### T-6.5 — Liste des credentials, libellés, bouton supprimer, route sécurisée (CSRF + propriétaire), mise à jour de la liste

Dans les paramètres utilisateur, afficher la liste des appareils pour lesquels un credential WebAuthn a été enregistré (appel au repository `findByUser()`). Pour chaque appareil : afficher un libellé (nom, date d'ajout ou « Appareil 1 », « Appareil 2 ») et un bouton « Supprimer ». Route de suppression sécurisée (CSRF + vérification que le credential appartient à l'utilisateur). Après suppression : mise à jour en BDD et rafraîchissement de la liste (ou redirection avec message).

---

### T-6.6 — Messages et fallback si WebAuthn non supporté

Gérer les navigateurs ou appareils qui ne supportent pas WebAuthn. Détecter le support côté client (ex. `window.PublicKeyCredential`). Si non supporté : masquer ou désactiver le bouton « Se connecter avec Face ID » sur le login et afficher un message court (ex. « Votre navigateur ne supporte pas la connexion par Face ID. Utilisez votre mot de passe. »). Dans les paramètres : masquer/désactiver la section d'enregistrement d'appareil et afficher un message explicatif. Éviter toute erreur JavaScript ou appel WebAuthn inutile ; messages en français et cohérents.

---

## Répartition sur 5 jours (plan détaillé)

### Jour 1 — Lundi

**Objectif :** Finaliser T-6.2 et la mettre en vérification ; démarrer T-6.3.

| Action | Détail |
|--------|--------|
| **En cours** | T-6.2 — Finir entité credential, migration, repository, remplacement Dummy, config bundle (~1h30). |
| **Déplacer** | Dès que T-6.2 est terminée → passer la carte T-6.2 en **À vérifier**. |
| **En cours** | Prendre T-6.3 depuis **À faire** → la mettre en **En cours** (route paramètres, bouton « Enregistrer cet appareil », WebAuthn create, etc.). |
| **Trello en fin de journée** | À faire : T-6.4, T-6.5, T-6.6 — En cours : T-6.3 — À vérifier : T-6.2 — Terminé : US-1 à US-3, US-4, US-5, T-6.1 |

---

### Jour 2 — Mardi

**Objectif :** Vérifier T-6.2 ; continuer / finir T-6.3.

| Action | Détail |
|--------|--------|
| **À vérifier** | Exécuter la checklist de vérification pour **T-6.2** (entités, BDD, pas de Dummy, repository utilisé). |
| **Déplacer** | Si la vérification est OK → T-6.2 → **Terminé**. |
| **En cours** | T-6.3 — Terminer route paramètres, envoi credential au serveur, message de succès (~2h au total sur Lundi + Mardi). |
| **Déplacer** | Quand T-6.3 est terminée → T-6.3 → **Terminé** ; T-6.4 → **En cours**. |
| **Trello en fin de journée** | À faire : T-6.5, T-6.6 — En cours : T-6.4 (ou encore T-6.3) — À vérifier : T-6.4 (plus tard) — Terminé : … + T-6.2 (si vérifiée), éventuellement T-6.3 |

---

### Jour 3 — Mercredi

**Objectif :** Finir T-6.3 si besoin ; avancer T-6.4 (bouton Face ID sur le login).

| Action | Détail |
|--------|--------|
| **En cours** | T-6.4 — Bouton Face ID sur le login, options d’assertion, `credentials.get()`, envoi assertion, connexion session (~1h30). |
| **Déplacer** | Quand T-6.4 est terminée → T-6.4 → **À vérifier** (tâche 2 à vérifier). T-6.5 → **En cours**. |
| **Trello en fin de journée** | À faire : T-6.6 — En cours : T-6.5 — À vérifier : T-6.4 — Terminé : T-6.2, T-6.3, + livrables initiaux |

---

### Jour 4 — Jeudi

**Objectif :** Vérifier T-6.4 ; faire T-6.5 (liste des credentials, supprimer).

| Action | Détail |
|--------|--------|
| **À vérifier** | Exécuter la checklist de vérification pour **T-6.4** (bouton login, parcours complet Face ID, gestion erreurs). |
| **Déplacer** | Si OK → T-6.4 → **Terminé**. |
| **En cours** | T-6.5 — Liste des credentials, libellés, bouton supprimer, route sécurisée (CSRF + propriétaire), mise à jour liste (~1h). |
| **Déplacer** | Quand T-6.5 est terminée → T-6.5 → **Terminé** ; T-6.6 → **En cours**. |
| **Trello en fin de journée** | À faire : — En cours : T-6.6 — À vérifier : — Terminé : … + T-6.4, T-6.5 |

---

### Jour 5 — Vendredi

**Objectif :** Finir T-6.6 (messages et fallback) ; tout passer en Terminé.

| Action | Détail |
|--------|--------|
| **En cours** | T-6.6 — Détection support WebAuthn, masquer bouton / section si non supporté, messages en français (~45min). |
| **Déplacer** | T-6.6 → **Terminé**. |
| **Bilan** | Toutes les cartes US-6 (T-6.2 à T-6.6) sont en **Terminé** ; les deux tâches à vérifier (T-6.2, T-6.4) ont été validées. |
| **Trello en fin de journée** | À faire : vide — En cours : vide — À vérifier : vide — Terminé : US-1 à US-3, US-4, US-5, T-6.1, T-6.2, T-6.3, T-6.4, T-6.5, T-6.6 |

---

### Récap visuel par jour

| Jour | À faire | En cours | À vérifier | Terminé |
|------|---------|----------|------------|---------|
| **Lundi** | T-6.4, T-6.5, T-6.6 | T-6.2 → puis T-6.3 | T-6.2 (en fin de journée) | US-1…US-5, T-6.1 |
| **Mardi** | T-6.4, T-6.5, T-6.6 | T-6.3 (puis T-6.4 en fin de journée) | — | + T-6.2 (si vérifiée), évent. T-6.3 |
| **Mercredi** | T-6.5, T-6.6 | T-6.4 puis T-6.5 | T-6.4 (en fin de journée) | + T-6.2, T-6.3 |
| **Jeudi** | T-6.6 | T-6.5 puis T-6.6 | — | + T-6.4, T-6.5 |
| **Vendredi** | — | — | — | + T-6.6 — Sprint 1 complet |

---

## Récap des listes Trello

- **À faire** : 4 cartes (T-6.3 à T-6.6).  
- **En cours** : 1 carte à la fois (T-6.2 au démarrage, puis T-6.3, T-6.4, etc.).  
- **À vérifier** : 2 tâches à vérifier — **T-6.2** (entités/repos) et **T-6.4** (login Face ID). Utiliser les checklists ci-dessus avant de passer en « Terminé ».  
- **Terminé** : 4 cartes déjà livrées (Module 1 base + T-6.1) + les cartes validées après « À vérifier ».

Flux : **À faire** → **En cours** → **À vérifier** (pour T-6.2 et T-6.4) → **Terminé**.
