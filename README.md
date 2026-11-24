# SAÉ 05 — Réseau Cocagne

Projet universitaire réalisé dans le cadre de la SAÉ 05 (BUT Informatique).  
Application web développée sous **Symfony 7** et conteneurisée avec **Docker Compose** (serveur PHP 8.4, base de données PostgreSQL et interface pgAdmin / Adminer).

---

## Équipe de développement

- **Alyssia Frey**
- **Asya Elik**

---

## Fonctionnalités & Sujets couverts

- **Logistique des tournées (Sujet B) :** Outil de planification et d'optimisation des tournées selon les paniers à livrer, la capacité des dépôts et les camionnettes disponibles.
- **Carte des livraisons (Sujet B) :** Visualisation cartographique interactive et tracé dynamique des itinéraires de livraison en fonction des points de collecte sélectionnés.
- **Gestion des recettes (Sujet C) :** Module d'administration et de consultation de fiches recettes culinaires avec moteur de recherche textuel.
- **API REST Sécurisée :** Interface de programmation (CRUD) protégée par authentification JWT (JSON Web Token) et documentée dynamiquement via Swagger/OpenAPI.

---

## Stack technique

- **Langage / Framework :** PHP 8.4, Symfony 7, Twig
- **Sécurité & API :** LexikJWTAuthenticationBundle, NelmioApiDocBundle (Swagger)
- **Base de données :** PostgreSQL 16
- **Administration BDD :** pgAdmin 4 / Adminer (avec persistance des données)
- **Conteneurisation :** Docker & Docker Compose

---

## Démarrage rapide

### 1. Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installé et démarré.
- [Git](https://git-scm.com/) installé.

---

### 2. Cloner le projet

```bash
git clone https://github.com/asoo58/SAE05.git
cd SAE05
```

### 3. Démarrer les conteneurs

```bash
docker compose up -d
```

### 4. Initialiser la base de données

```bash
# Créer la base de données si elle n'existe pas encore
docker compose exec php php bin/console doctrine:database:create --if-not-exists

# Appliquer les migrations de schéma
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Accès aux services

- **Application web :** http://localhost:8090
- **Documentation API (Swagger) :** http://localhost:8090/api/doc
- **Gestion BDD (Adminer / pgAdmin) :** http://localhost:8091 ou http://localhost:5050 *(voir ports exacts et identifiants dans le `.env`)*


## Utilisation de l'API

L'API est sécurisée par **JWT (JSON Web Token)**.  
Avant de pouvoir interroger les différentes routes CRUD (`/api/...`), il est nécessaire de générer un token d'authentification.

### 1. Générer un token JWT

#### Option 1 : Avec Postman

Envoyez une requête `POST` sur la route d'authentification :

- **Endpoint :** `POST http://localhost:8090/api/login_check`
- **En-tête :** `Content-Type: application/json`
- **Corps de la requête (JSON) :**

```json
{
  "username": "prof@cocagne.fr",
  "password": "test"
}
```

#### Option 2 : Directement sur le site

Allez dans la section **Authentification** sur la documentation de l'API et générez un token avec les identifiants ci-dessus.

Dans les deux cas, la réponse renvoie un objet JSON contenant votre `token`.

### 2. S'authentifier sur Swagger

1. Ouvrez la documentation interactive : [http://localhost:8090/documentation-api](http://localhost:8090/documentation-api).
2. Cliquez sur le bouton **Authorize** (en haut à droite).
3. Collez le token récupéré à l'étape précédente et validez.
4. Vous pouvez maintenant tester l'ensemble des routes protégées de l'API directement depuis l'interface.

### 3. Utilisation via un client externe (Postman, Insomnia, cURL)

Pour chaque appel vers une route protégée, ajoutez l'en-tête HTTP suivant :

```http
Authorization: Bearer <votre_token_jwt>
```