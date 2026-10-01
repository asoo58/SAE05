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

---

## Stack technique

- **Langage / Framework :** PHP 8.4, Symfony 7, Twig
- **Base de données :** PostgreSQL 16
- **Administration BDD :** pgAdmin 4 / Adminer
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
### 3. Démarré le container

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
### Accès aux services

- Application web : http://localhost:8090
- Gestion BDD (Adminer / pgAdmin) : http://localhost:8091
- ( voir les identifiants dans le .env)