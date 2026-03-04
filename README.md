# CHELSY Restaurant — API & Back-office

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql)](https://mysql.com)
[![Laravel Sanctum](https://img.shields.io/badge/Sanctum-4.x-FF2D20?style=flat-square)](https://laravel.com/docs/sanctum)
[![Stripe](https://img.shields.io/badge/Stripe-Paiements-008CDD?style=flat-square&logo=stripe)](https://stripe.com)
[![Swagger](https://img.shields.io/badge/Swagger-OpenAPI-85EA2D?style=flat-square&logo=swagger)](https://swagger.io)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.x-38B2AC?style=flat-square&logo=tailwind-css)](https://tailwindcss.com)
[![Vite](https://img.shields.io/badge/Vite-7.x-646CFF?style=flat-square&logo=vite)](https://vitejs.dev)
[![Firebase](https://img.shields.io/badge/Firebase-FCM-FFCA28?style=flat-square&logo=firebase)](https://firebase.google.com)
[![DomPDF](https://img.shields.io/badge/DomPDF-Factures-333?style=flat-square)](https://github.com/dompdf/dompdf)
[![Intervention Image](https://img.shields.io/badge/Intervention-Image-4A90D9?style=flat-square)](http://image.intervention.io)

API REST et dashboard d’administration pour la gestion d’un restaurant : commandes en ligne, paiements, notifications push et suivi des livreurs.



## Démo en ligne

**Application :** [https://chelsy-api.cabinet-xaviertermeau.com/](https://chelsy-api.cabinet-xaviertermeau.com/)

### Compte administrateur de test

| Champ        | Valeur                     |
|-------------|----------------------------|
| **Email**   | `admin@chelsy-restaurant.bj` |
| **Mot de passe** | `admin123`            |



## Aperçu du dashboard admin

### Tableau de bord

![Dashboard](docs/dashboard.png)

### Gestion des catégories

![Catégories](docs/category_dash.png)

### Gestion des plats

![Plats](docs/dish_dash.png)



## Fonctionnalités

- **Authentification** — Inscription, connexion, réinitialisation mot de passe
- **Utilisateur** — Profil, adresses, avatar
- **Catalogue** — Catégories, plats, avis, filtres
- **Panier** — Gestion avec options et personnalisation
- **Commandes** — Création, suivi, annulation, factures PDF
- **Paiements** — Espèces, carte (Stripe), Mobile Money (FedaPay)
- **Avis & notations** — Restaurant, plats, livraison
- **Favoris, codes promo, FAQ, réclamations**
- **Dashboard admin** — Gestion des entités, import/export CSV
- **Notifications push** — Firebase Cloud Messaging
- **Suivi GPS** — Position des livreurs en temps réel
- **Documentation API** — Swagger/OpenAPI



## Technologies

| Backend | Frontend / Outils |
|--------|--------------------|
| Laravel 12, PHP 8.2+ | Vite, Tailwind CSS 4, Alpine.js |
| MySQL | Swagger (l5-swagger) |
| Laravel Sanctum | DomPDF, Intervention Image |
| Stripe, FedaPay | Firebase (FCM, JWT) |



## Installation

### Prérequis

- PHP 8.2+, Composer, MySQL, Node.js 18+

### Étapes

```bash
git clone https://github.com/iamrachking/api-chelsy-apk
cd api-chelsy-apk
```

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configurer la base de données dans `.env`, puis :

```bash
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
```

### Configuration optionnelle

- **Firebase** — Fichier de credentials dans `storage/app/firebase-credentials.json` ou `FIREBASE_SERVER_KEY` dans `.env`
- **Stripe** — `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` dans `.env`


## Documentation API

Générer la doc Swagger :

```bash
php artisan l5-swagger:generate
```

Accès : `http://votre-domaine/api/documentation`

Le fichier `CHELSY_API.http` permet de tester les endpoints (extension REST Client sous VS Code).

### Exemples d’endpoints

| Méthode | Route | Description |
|--------|--------|-------------|
| POST | `/api/v1/register` | Inscription |
| POST | `/api/v1/login` | Connexion |
| GET  | `/api/v1/me` | Utilisateur connecté |
| GET  | `/api/v1/orders` | Liste des commandes |
| POST | `/api/v1/orders` | Créer une commande |
| GET  | `/api/v1/orders/{id}/tracking` | Suivi livraison |


## Commandes utiles

```bash
php artisan l5-swagger:generate   # Régénérer la doc Swagger
php artisan config:clear && php artisan cache:clear
php artisan migrate:fresh --seed   # Réinitialiser la BDD avec seed
```


## Licence

MIT — Projet développé pour **CHELSY Restaurant**.
