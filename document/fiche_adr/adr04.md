# ADR 04 : Stratégie d'isolation multi-structures (Multi-tenancy)

* **Date :** Octobre 2024
* **Statut :** Accepté
* **Auteurs :** Alyssia Frey, Asya Elik
* **Contexte technique :** Cloisonnement des données inter-associations (SAÉ 5)

---

## 1. Contexte & Problématique

Le Réseau Cocagne fédère une centaine de chantiers d'insertion (ACI) en maraîchage biologique. Chaque Jardin est une association loi 1901 juridiquement indépendante. 

Bien que l'infrastructure logicielle soit mutualisée à l'échelle nationale pour réduire les coûts d'hébergement, **il est strictement interdit qu'une association puisse accéder, consulter ou modifier les adhérents, tournées ou bilans comptables d'une autre structure**.

---

## 2. Alternatives analysées

### Option 1 : Une base de données distincte par structure (Database per Tenant)
* **Avantages :** Étanchéité physique absolue des données.
* **Inconvénients :** Coût d'exploitation prohibitif, complexité ingérable pour appliquer les migrations de schéma sur 100 bases distinctes, surconsommation de mémoire pour les connexions simultanées.

### Option 2 : Un schéma PostgreSQL par structure (Schema per Tenant)
* **Avantages :** Bonne séparation logique au sein d'une même base de données.
* **Inconvénients :** Configuration complexe du pooling de connexions dans Doctrine, migrations lentes et lourdes à chaque mise à jour applicative.

### Option 3 : Base partagée avec clé de partitionnement et filtre Doctrine global (Shared Database, Discriminator Column)
* **Avantages :** Schéma unique, migrations instantanées pour tout le réseau, utilisation minimale de mémoire vive.
* **Inconvénients :** Risque critique de fuite de données en cas d'oubli d'une clause `WHERE structure_id = ...` par un développeur dans un contrôleur ou un repository.

---

## 3. Décision arbitrée

Nous retenons l'**Option 3 sécurisée : Base partagée avec clé `structure_id` et activation d'un filtre SQL Doctrine global (`Doctrine SQL Filter`)**.

Chaque entité de gestion (Adhérent, Dépôt, Tournée, Véhicule) intègre une clé étrangère obligatoire `structure_id`. Un filtre Doctrine actif sur l'ensemble de la session injecte automatiquement et de manière transparente la contrainte SQL `WHERE structure_id = :current_structure_id` sur 100 % des requêtes générées par l'ORM.

---

## 4. Conséquences & Risques acceptés

* **Positives :**
  * Zéro risque d'oubli humain dans les requêtes de l'équipe : le filtre s'applique au niveau du moteur de persistance.
  * Déploiement et migrations de schéma ultra-rapides via une seule commande `doctrine:migrations:migrate`.
* **Négatives / Risques compensés :**
  * Les requêtes natives SQL directes (DQL contourné) échappent au filtre ; l'utilisation de requêtes SQL brutes est donc strictement encadrée et bannie hors des méthodes de consultation globale réservées au `ROLE_SUPER_ADMIN`.