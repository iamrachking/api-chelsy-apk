# Guide d'Implémentation - Notifications Push (Firebase Cloud Messaging)

## 📋 Ce qui est nécessaire pour FCM

### 1. Configuration Firebase

#### A. Créer un projet Firebase
1. Aller sur [Firebase Console](https://console.firebase.google.com/)
2. Créer un nouveau projet ou utiliser un projet existant
3. Activer **Cloud Messaging** dans le projet

#### B. Obtenir les clés
1. **Server Key** (pour l'API backend)
   - Aller dans **Project Settings** > **Cloud Messaging**
   - Copier la **Server Key** (ancienne API) ou créer une **Service Account**

2. **Service Account JSON** (recommandé)
   - Aller dans **Project Settings** > **Service Accounts**
   - Cliquer sur **Generate new private key**
   - Télécharger le fichier JSON

### 2. Installation des dépendances

```bash
composer require kreait/firebase-php
```

### 3. Configuration Laravel

#### A. Variables d'environnement (.env)
```env
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
# OU
FIREBASE_PROJECT_ID=votre-project-id
FIREBASE_CLIENT_EMAIL=votre-service-account@project.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
```

#### B. Fichier de configuration (config/firebase.php)
```php
return [
    'credentials' => env('FIREBASE_CREDENTIALS'),
    'project_id' => env('FIREBASE_PROJECT_ID'),
];
```

### 4. Migration pour stocker les tokens FCM

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('fcm_token')->nullable()->after('avatar');
    $table->timestamp('fcm_token_updated_at')->nullable();
});
```

### 5. Service de notification

Créer `app/Services/NotificationService.php` :
- Méthode pour envoyer des notifications
- Gestion des tokens FCM
- Envoi de notifications lors des changements de statut de commande
- Envoi de notifications pour les réponses aux réclamations

### 6. Endpoints API nécessaires

#### A. Enregistrer/Mettre à jour le token FCM
```
POST /api/v1/fcm-token
Body: { "token": "fcm_token_string" }
```

#### B. Supprimer le token FCM (déconnexion)
```
DELETE /api/v1/fcm-token
```

### 7. Intégration dans les contrôleurs

- **OrderController** : Envoyer notification lors du changement de statut
- **ComplaintController** : Envoyer notification lors de la réponse admin
- **PaymentController** : Envoyer notification lors de la confirmation de paiement

### 8. Structure des notifications

```json
{
  "title": "Titre de la notification",
  "body": "Corps du message",
  "data": {
    "type": "order_status_update",
    "order_id": 123,
    "status": "confirmed"
  }
}
```

### 9. Côté Flutter (App Mobile)

L'app Flutter doit :
- Installer le package `firebase_messaging`
- Demander les permissions de notification
- Enregistrer le token FCM
- Envoyer le token à l'API lors de la connexion
- Gérer les notifications en arrière-plan et au premier plan

---

## 📝 Résumé des étapes

1. ✅ Créer projet Firebase
2. ✅ Obtenir les clés/credentials
3. ✅ Installer `kreait/firebase-php`
4. ✅ Configurer les variables d'environnement
5. ✅ Créer la migration pour `fcm_token`
6. ✅ Créer le service `NotificationService`
7. ✅ Créer les endpoints API pour gérer les tokens
8. ✅ Intégrer dans les contrôleurs existants
9. ✅ Tester les notifications

---

## ⚠️ Notes importantes

- Les tokens FCM peuvent expirer et doivent être mis à jour régulièrement
- Gérer les erreurs (token invalide, utilisateur déconnecté)
- Les notifications doivent être pertinentes et non intrusives
- Respecter les préférences de notification de l'utilisateur (si implémenté)

