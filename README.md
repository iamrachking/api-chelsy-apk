# 🍽️ API CHELSY Restaurant

API REST complète pour la gestion d'un restaurant avec système de commandes, paiements, notifications push et suivi GPS des livreurs.

## 📋 Table des matières

- [Fonctionnalités](#-fonctionnalités)
- [Technologies](#-technologies)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Documentation API](#-documentation-api)
- [Tests](#-tests)
- [Structure du projet](#-structure-du-projet)
- [État des fonctionnalités](#-état-des-fonctionnalités)

## ✨ Fonctionnalités

### ✅ Implémentées (17 fonctionnalités - 94%)

1. **Authentification complète**
   - Inscription, connexion, déconnexion
   - Réinitialisation de mot de passe
   - Gestion du profil utilisateur

2. **Gestion utilisateur**
   - Profil utilisateur avec avatar
   - Gestion des adresses (CRUD complet)
   - Blocage/déblocage utilisateurs (admin)

3. **Catalogue**
   - Liste des catégories et plats
   - Filtres avancés (catégorie, végétarien, prix)
   - Plats mis en avant et populaires
   - Avis publics des plats

4. **Panier**
   - Gestion complète du panier
   - Options de personnalisation des plats
   - Calcul automatique des totaux

5. **Commandes**
   - Création de commandes
   - Suivi des statuts
   - Annulation de commandes
   - Recommandation de commandes
   - Génération de factures PDF

6. **Paiements**
   - Paiement en espèces
   - Paiement par carte bancaire (Stripe)
   - Paiement Mobile Money (structure prête)

7. **Avis et notations**
   - Notation du restaurant, plats et livraison
   - Commentaires et images

8. **Favoris**
   - Ajout/suppression de plats favoris

9. **Codes promo**
   - Validation et application automatique
   - CRUD complet (admin)
   - Statistiques d'utilisation

10. **FAQ**
    - Liste des questions fréquentes
    - CRUD complet (admin)

11. **Réclamations**
    - Création et suivi des réclamations
    - CRUD complet (admin)
    - Réponses admin

12. **Dashboard admin**
    - Statistiques générales
    - Gestion complète de toutes les entités

13. **Import/Export de données**
    - Import de catégories et plats (CSV)
    - Export des utilisateurs et statistiques (CSV)

14. **Notifications Push (FCM)**
    - Enregistrement des tokens FCM
    - Notifications automatiques (commandes, statuts, paiements)
    - Support Service Account JSON et Server Key

15. **Documentation Swagger**
    - Documentation complète de tous les endpoints
    - Interface Swagger UI interactive

16. **Suivi GPS Livreur**
    - Mise à jour de position en temps réel
    - Suivi des commandes pour les clients
    - Calcul automatique de l'ETA et de la distance
    - Liste des livreurs disponibles (admin)

17. **Sécurité**
    - Authentification Sanctum
    - Protection CSRF
    - Validation des données
    - Fichiers sensibles exclus de Git

### ⚠️ Partiellement implémentées (à laisser pour le moment)

- **Paiement Mobile Money** : Structure prête, simulation uniquement
- **Webhook Stripe** : Structure de base, vérification de signature manquante

### ❌ Laissées de côté (non prioritaires)

- **Export des commandes** : Peut être ajouté plus tard si nécessaire

## 🛠️ Technologies

- **Framework** : Laravel 12.x
- **PHP** : 8.2+
- **Base de données** : MySQL/MariaDB
- **Authentification** : Laravel Sanctum
- **Paiements** : Stripe
- **Notifications** : Firebase Cloud Messaging (FCM)
- **Documentation** : Swagger/OpenAPI (L5-Swagger)
- **PDF** : DomPDF
- **Images** : Intervention Image

## 📦 Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL/MariaDB
- Node.js et npm (pour les assets)
- Laragon (recommandé pour Windows) ou équivalent

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone <url-du-repo>
cd api-chelsy-apk
```

### 2. Installer les dépendances

```bash
composer install
npm install
```

### 3. Configuration de l'environnement

Copiez le fichier `.env.example` vers `.env` :

```bash
cp .env.example .env
```

Générez la clé d'application :

```bash
php artisan key:generate
```

### 4. Configuration de la base de données

Éditez le fichier `.env` et configurez votre base de données :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chelsy_restaurant
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Migrations et seeders

```bash
php artisan migrate
php artisan db:seed
```

### 6. Configuration Firebase (pour les notifications push)

#### Option 1 : Service Account JSON (Recommandé)

1. Téléchargez le fichier JSON de votre projet Firebase
2. Placez-le dans `storage/app/firebase-credentials.json`
3. Configurez dans `.env` :

```env
FIREBASE_CREDENTIALS_PATH=storage/app/firebase-credentials.json
FIREBASE_PROJECT_ID=chelsy-restaurant
```

#### Option 2 : Server Key (Alternative)

```env
FIREBASE_SERVER_KEY=votre_server_key_ici
FIREBASE_PROJECT_ID=chelsy-restaurant
```

⚠️ **Important** : Les fichiers Firebase sont exclus de Git (voir `.gitignore`)

### 7. Configuration Stripe (pour les paiements)

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### 8. Lien symbolique pour le stockage

```bash
php artisan storage:link
```

### 9. Compiler les assets (si nécessaire)

```bash
npm run build
```

## ⚙️ Configuration

### Variables d'environnement importantes

```env
APP_NAME="CHELSY Restaurant"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://api-chelsy-apk.test

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chelsy_restaurant
DB_USERNAME=root
DB_PASSWORD=

# Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# Firebase
FIREBASE_CREDENTIALS_PATH=storage/app/firebase-credentials.json
FIREBASE_SERVER_KEY=
FIREBASE_PROJECT_ID=chelsy-restaurant

# Mail (pour la réinitialisation de mot de passe)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
```

## 📚 Documentation API

### Génération de la documentation Swagger

```bash
php artisan l5-swagger:generate
```

Accédez à la documentation via : `http://api-chelsy-apk.test/api/documentation`

### Tester l'API avec REST Client

Le fichier `CHELSY_API.http` contient tous les endpoints testables avec l'extension REST Client de VS Code.

1. Installez l'extension "REST Client" dans VS Code
2. Ouvrez `CHELSY_API.http`
3. Suivez les étapes numérotées
4. Après le login, copiez le token et collez-le dans la variable `@token`

### Endpoints principaux

#### Authentification
- `POST /api/v1/register` - Inscription
- `POST /api/v1/login` - Connexion
- `POST /api/v1/logout` - Déconnexion
- `GET /api/v1/me` - Utilisateur connecté
- `POST /api/v1/forgot-password` - Mot de passe oublié
- `POST /api/v1/reset-password` - Réinitialisation

#### Commandes
- `GET /api/v1/orders` - Liste des commandes
- `POST /api/v1/orders` - Créer une commande
- `GET /api/v1/orders/{id}` - Détails d'une commande
- `POST /api/v1/orders/{id}/cancel` - Annuler une commande
- `GET /api/v1/orders/{id}/tracking` - Suivi GPS (client)

#### Notifications FCM
- `POST /api/v1/fcm-token` - Enregistrer le token FCM
- `DELETE /api/v1/fcm-token` - Supprimer le token FCM

#### Suivi GPS Livreur
- `POST /api/v1/delivery/position` - Mettre à jour la position (livreur)
- `GET /api/v1/delivery/position/current` - Position actuelle (livreur)
- `GET /api/v1/delivery/position/history` - Historique (livreur)
- `GET /api/v1/delivery/drivers/available` - Liste livreurs (admin)

Voir `CHELSY_API.http` pour la liste complète des endpoints.

## 🧪 Tests

```bash
php artisan test
```

## 📁 Structure du projet

```
api-chelsy-apk/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/          # Contrôleurs API
│   │   │   └── Admin/Web/       # Contrôleurs Admin
│   │   ├── Middleware/           # Middlewares
│   │   ├── Requests/            # Form Requests
│   │   └── Resources/            # API Resources
│   ├── Models/                   # Modèles Eloquent
│   └── Services/                 # Services métier
│       ├── NotificationService.php
│       ├── PaymentService.php
│       ├── DeliveryService.php
│       └── InvoiceService.php
├── database/
│   ├── migrations/               # Migrations
│   └── seeders/                 # Seeders
├── routes/
│   ├── api.php                   # Routes API
│   └── web.php                   # Routes Web (Admin)
├── resources/
│   └── views/
│       └── admin/                # Vues Blade (Admin)
├── storage/
│   └── app/
│       └── firebase-credentials.json  # Fichier Firebase (à ajouter)
├── CHELSY_API.http               # Fichier de test REST Client
├── ETAT_FONCTIONNALITES.md       # État détaillé des fonctionnalités
└── README.md                     # Ce fichier
```

## 📊 État des fonctionnalités

Consultez le fichier `ETAT_FONCTIONNALITES.md` pour un état détaillé de toutes les fonctionnalités.

**Résumé** :
- ✅ **17 fonctionnalités complètes** (94%)
- ⚠️ **2 fonctionnalités partiellement implémentées** (à laisser pour le moment)
- ❌ **1 fonctionnalité laissée de côté** (non prioritaire)

## 🔧 Commandes utiles

### Générer la documentation Swagger
```bash
php artisan l5-swagger:generate
```

### Vider les caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Migrations
```bash
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed
```

### Créer un utilisateur admin
```bash
php artisan tinker
```
Puis dans Tinker :
```php
User::create([
    'firstname' => 'Admin',
    'lastname' => 'User',
    'email' => 'admin@chelsy.com',
    'password' => Hash::make('password'),
    'is_admin' => true,
]);
```

## 🔐 Sécurité

- Les fichiers Firebase sont exclus de Git (`.gitignore`)
- Authentification via Laravel Sanctum
- Protection CSRF activée
- Validation des données sur tous les endpoints
- Middleware de blocage utilisateur

## 📝 Licence

Ce projet est un projet académique.

## 👥 Support

Pour toute question ou problème, consultez :
- La documentation Swagger : `http://api-chelsy-apk.test/api/documentation`
- Le fichier `ETAT_FONCTIONNALITES.md` pour l'état des fonctionnalités
- Le fichier `CHELSY_API.http` pour des exemples de requêtes

---

**Développé avec ❤️ pour CHELSY Restaurant**
