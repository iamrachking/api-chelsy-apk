# Guide de Test de l'API CHELSY

Ce guide vous permet de tester systématiquement tous les endpoints de l'API.

## Prérequis

1. Le serveur Laravel doit être en cours d'exécution (via Laragon)
2. L'extension REST Client doit être installée dans VS Code
3. Ouvrez le fichier `CHELSY_API.http`

## Ordre de Test Recommandé

### ÉTAPE 1 : Tests Publics (sans authentification)

Testez ces endpoints en premier car ils ne nécessitent pas de token :

1. **Restaurant** - `GET /api/v1/restaurant`
2. **Catégories** - `GET /api/v1/categories`
3. **Catégorie spécifique** - `GET /api/v1/categories/1`
4. **Plats** - `GET /api/v1/dishes`
5. **Plat spécifique** - `GET /api/v1/dishes/1`
6. **FAQ** - `GET /api/v1/faqs`

### ÉTAPE 2 : Authentification

1. **Register** - Créez un compte de test
2. **Login** - Connectez-vous et **COPIEZ LE TOKEN** de la réponse
3. **Mettez à jour la variable `@token`** dans le fichier `.http`
4. **Me** - Vérifiez que vous êtes bien connecté

### ÉTAPE 3 : Profil Utilisateur

1. **Get Profile** - `GET /api/v1/profile`
2. **Update Profile** - `PUT /api/v1/profile`

### ÉTAPE 4 : Adresses

1. **List Addresses** - `GET /api/v1/addresses`
2. **Create Address** - `POST /api/v1/addresses` (IMPORTANT : notez l'ID retourné)
3. **Get Address** - `GET /api/v1/addresses/{id}`
4. **Update Address** - `PUT /api/v1/addresses/{id}`

### ÉTAPE 5 : Panier

1. **Get Cart** - `GET /api/v1/cart` (devrait être vide)
2. **Add to Cart** - `POST /api/v1/cart/items` (ajoutez au moins 2 plats différents)
3. **Get Cart** - Vérifiez que les articles sont bien présents
4. **Update Cart Item** - `PUT /api/v1/cart/items/{id}`
5. **Get Cart** - Vérifiez la mise à jour

### ÉTAPE 6 : Commandes

**IMPORTANT** : Assurez-vous d'avoir :
- Au moins une adresse créée (Étape 4)
- Des articles dans le panier (Étape 5)

1. **List Orders** - `GET /api/v1/orders` (devrait être vide)
2. **Create Order (Cash)** - `POST /api/v1/orders` avec `payment_method: "cash"`
3. **List Orders** - Vérifiez que la commande apparaît
4. **Get Order** - `GET /api/v1/orders/{id}` (utilisez l'ID de la commande créée)
5. **Get Invoice** - `GET /api/v1/orders/{id}/invoice`

### ÉTAPE 7 : Autres Fonctionnalités

1. **Favorites** - Ajouter/supprimer des favoris
2. **Promo Codes** - Valider un code promo
3. **Complaints** - Créer une réclamation
4. **FCM Token** - Enregistrer un token FCM (pour les notifications)

### ÉTAPE 8 : Tests Avancés (si applicable)

1. **GPS Tracking** - Si vous avez un compte livreur (`is_driver: true`)
2. **Reviews** - Créer un avis (nécessite une commande livrée)

## Points d'Attention

- **Token** : Après chaque login, mettez à jour la variable `@token` dans le fichier `.http`
- **IDs** : Notez les IDs retournés (adresses, commandes, etc.) pour les utiliser dans les requêtes suivantes
- **Panier** : La création de commande utilise automatiquement le contenu du panier
- **Adresses** : Nécessaires uniquement pour les commandes de type `delivery`
- **Erreurs** : Si une requête échoue, vérifiez :
  - Le token est valide et à jour
  - Les IDs utilisés existent
  - Les données envoyées respectent les règles de validation

## Erreurs Courantes

1. **401 Unauthorized** : Token manquant ou expiré → Reconnectez-vous
2. **422 Validation Error** : Données invalides → Vérifiez le format des données
3. **404 Not Found** : ID inexistant → Vérifiez que l'ID existe
4. **403 Forbidden** : Compte bloqué ou permissions insuffisantes

## Commandes Utiles

Pour vérifier l'état de la base de données :
- Ouvrez phpMyAdmin via Laragon
- Vérifiez les tables : `users`, `orders`, `addresses`, `carts`, etc.

Pour voir les logs d'erreur :
- Fichier : `storage/logs/laravel.log`

