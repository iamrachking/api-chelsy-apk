# API CHELSY Restaurant

API REST pour la gestion d'un restaurant avec commandes en ligne, paiements, notifications push et suivi GPS des livreurs.

## Ce qui est disponible

L'API couvre les fonctionnalités principales d'un restaurant en ligne :

- **Authentification** : inscription, connexion, réinitialisation de mot de passe
- **Gestion utilisateur** : profil, adresses, avatar
- **Catalogue** : catégories, plats avec filtres, avis
- **Panier** : gestion complète avec options de personnalisation
- **Commandes** : création, suivi, annulation, factures PDF
- **Paiements** : espèces, carte bancaire (Stripe), Mobile Money (structure prête)
- **Avis et notations** : restaurant, plats, livraison
- **Favoris** : sauvegarde des plats préférés
- **Codes promo** : validation et application automatique
- **FAQ** : questions fréquentes
- **Réclamations** : système de tickets
- **Dashboard admin** : gestion complète de toutes les entités
- **Import/Export** : catégories et plats en CSV
- **Notifications push** : Firebase Cloud Messaging
- **Suivi GPS** : position des livreurs en temps réel
- **Documentation Swagger** : tous les endpoints documentés

## Technologies utilisées

- Laravel 12.x
- PHP 8.2+
- MySQL
- Laravel Sanctum (authentification)
- Stripe (paiements)
- Firebase Cloud Messaging (notifications)
- Swagger/OpenAPI (documentation)
- DomPDF (factures)
- Intervention Image (traitement d'images)

## Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL
- Node.js et npm
- Laragon (recommandé pour Windows - perso ça me facilite la vie)

### Étapes

1. **Cloner le projet**
```bash
git clone https://github.com/iamrachking/api-chelsy-apk
cd api-chelsy-apk
```

2. **Installer les dépendances**
```bash
composer install
npm install
```

3. **Configuration**

Copiez `.env.example` vers `.env` :
```bash
cp .env.example .env
```

Générez la clé d'application :
```bash
php artisan key:generate
```

4. **Base de données**

Configurez votre base de données dans `.env` :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chelsy_restaurant
DB_USERNAME=root
DB_PASSWORD=
```

Puis exécutez les migrations :
```bash
php artisan migrate --seed
```

5. **Firebase (notifications push)**

Placez votre fichier JSON Firebase dans `storage/app/firebase-credentials.json` et ajoutez dans `.env` :
```env
FIREBASE_CREDENTIALS_PATH=storage/app/firebase-credentials.json
FIREBASE_PROJECT_ID=chelsy-restaurant
```

Ou utilisez une Server Key :
```env
FIREBASE_SERVER_KEY=votre_server_key_ici
FIREBASE_PROJECT_ID=chelsy-restaurant
```

6. **Stripe (paiements)**

Ajoutez vos clés Stripe dans `.env` :
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

7. **Lien symbolique**
```bash
php artisan storage:link
```

8. **Compiler les assets** (si nécessaire)
```bash
npm run build
```

## Documentation API

### Swagger

Générez la documentation :
```bash
php artisan l5-swagger:generate
```

Accédez à la documentation : `http://api-chelsy-apk.test/api/documentation`

### Tester avec REST Client

Le fichier `CHELSY_API.http` contient tous les endpoints testables. Vous pouvez utiliser REST Client ou un autre outil si vous avez du temps pour s'amuser.

1. Installez l'extension "REST Client" dans VS Code
2. Ouvrez `CHELSY_API.http`
3. Suivez les étapes numérotées
4. Après le login, copiez le token dans la variable `@token`

### Quelques endpoints

**Authentification**
- `POST /api/v1/register` - Inscription
- `POST /api/v1/login` - Connexion
- `POST /api/v1/logout` - Déconnexion
- `GET /api/v1/me` - Utilisateur connecté

**Commandes**
- `GET /api/v1/orders` - Liste des commandes
- `POST /api/v1/orders` - Créer une commande
- `GET /api/v1/orders/{id}` - Détails d'une commande
- `GET /api/v1/orders/{id}/tracking` - Suivi GPS

**Notifications**
- `POST /api/v1/fcm-token` - Enregistrer le token FCM
- `DELETE /api/v1/fcm-token` - Supprimer le token FCM

**Suivi GPS**
- `POST /api/v1/delivery/position` - Mettre à jour la position (livreur)
- `GET /api/v1/orders/{order_id}/tracking` - Suivre une commande (client)

Voir `CHELSY_API.http` pour la liste complète.

## Structure du projet

```
app/
├── Http/Controllers/
│   ├── Api/V1/          # Contrôleurs API
│   └── Admin/Web/       # Contrôleurs Admin
├── Models/              # Modèles Eloquent
└── Services/            # Services métier
    ├── NotificationService.php
    ├── PaymentService.php
    ├── DeliveryService.php
    └── InvoiceService.php

database/
├── migrations/          # Migrations
└── seeders/            # Seeders

routes/
├── api.php              # Routes API
└── web.php              # Routes Web (Admin)
```

## Commandes utiles

**Documentation Swagger**
```bash
php artisan l5-swagger:generate
```

**Vider les caches** (parfois ça m'embête mais c'est nécessaire)
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

**Migrations**
```bash
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed
```

## Compte admin par défaut

Après avoir exécuté les seeders :
- Email : `admin@chelsy-restaurant.bj`
- Mot de passe : `admin123`

## Notes importantes

- Les fichiers Firebase sont exclus de Git (voir `.gitignore`)
- L'authentification utilise Laravel Sanctum
- Tous les endpoints sont validés
- Le middleware bloque les utilisateurs désactivés

## Support

Pour toute question ou problème :
- Documentation Swagger : `http://api-chelsy-apk.test/api/documentation`
- Fichier `CHELSY_API.http` pour tester les endpoints avec REST Client

En cas de problème, contactez-moi sur WhatsApp : https://wa.me/22991112763

Développé pour CHELSY Restaurant
