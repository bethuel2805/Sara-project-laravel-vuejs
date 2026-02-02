# Backend Laravel - API Sara Stock

Ce backend Laravel remplace le backend Node.js précédent. Il fournit une API REST complète pour la gestion de stock.

## Installation

1. Installer les dépendances Composer :
```bash
composer install
```

2. Copier le fichier `.env.example` vers `.env` :
```bash
cp .env.example .env
```

3. Générer la clé d'application :
```bash
php artisan key:generate
```

4. Configurer la base de données dans le fichier `.env` :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sara_stock
DB_USERNAME=root
DB_PASSWORD=
```

5. Exécuter les migrations :
```bash
php artisan migrate
```

6. Le secret JWT a déjà été généré. Si besoin, vous pouvez le régénérer :
```bash
php artisan jwt:secret
```

## Démarrage du serveur

```bash
php artisan serve
```

Le serveur sera accessible sur `http://localhost:8000`

## Configuration du Frontend

Pour que le frontend Vue.js puisse communiquer avec ce backend Laravel, vous devez mettre à jour l'URL de l'API dans le fichier `frontend/src/services/api.ts` :

```typescript
export const API_BASE_URL = 'http://localhost:8000/api/v1'
```

## Routes API

Toutes les routes sont préfixées par `/api/v1`.

### Authentification

- `POST /api/v1/auth/register` - Inscription d'un nouvel utilisateur
- `POST /api/v1/auth/login` - Connexion
- `GET /api/v1/auth/me` - Obtenir l'utilisateur connecté (nécessite authentification)

### Catégories

- `GET /api/v1/categories` - Liste des catégories (nécessite authentification)
- `POST /api/v1/categories` - Créer une catégorie (nécessite authentification)

### Produits

- `GET /api/v1/products` - Liste des produits (nécessite authentification)
- `POST /api/v1/products` - Créer un produit (nécessite authentification)

### Utilisateurs (Admin uniquement)

- `GET /api/v1/users` - Liste des utilisateurs
- `GET /api/v1/users/{id}` - Obtenir un utilisateur
- `POST /api/v1/users` - Créer un utilisateur
- `PUT /api/v1/users/{id}` - Mettre à jour un utilisateur
- `DELETE /api/v1/users/{id}` - Supprimer un utilisateur

## Authentification

L'API utilise JWT (JSON Web Tokens) pour l'authentification. 

Pour les requêtes authentifiées, inclure le header :
```
Authorization: Bearer {token}
```

Le token est valide pendant 8 heures.

## Rôles utilisateurs

- `admin` : Accès complet, peut gérer les utilisateurs
- `gestionnaire` : Peut gérer les catégories et produits
- `observateur` : Accès en lecture seule

## CORS

Le CORS est configuré pour accepter les requêtes depuis :
- http://localhost:5173
- http://localhost:5174
- http://localhost:5175
- http://localhost:3000

## Structure

- `app/Http/Controllers/Api/` - Controllers API
- `app/Models/` - Modèles Eloquent
- `app/Http/Middleware/` - Middlewares personnalisés
- `routes/api.php` - Routes API
- `database/migrations/` - Migrations de base de données
