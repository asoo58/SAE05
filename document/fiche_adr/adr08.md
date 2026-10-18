# ADR 08 : Moteur de recherche textuelle des recettes (PostgreSQL FTS + pg_trgm)

* **Date :** Octobre 2024
* **Statut :** Accepté
* **Auteurs :** Alyssia Frey, Asya Elik
* **Contexte technique :** Module Recettes culinaires - Sujet C (SAÉ 5)

---

## 1. Contexte & Problématique

Le Sujet C demande de concevoir une rubrique permettant aux adhérents de gérer et de rechercher des recettes de cuisine afin de valoriser les légumes reçus dans les paniers hebdomadaires.

Le moteur de recherche textuel doit :
1. Chercher simultanément dans le titre, la liste des ingrédients et les étapes de préparation.
2. Gérer les déclinaisons de la langue française (singulier/pluriel, verbes conjugués).
3. Tolérer les fautes de frappe et proposer de l'autocomplétion rapide.

---

## 2. Alternatives analysées

### Option 1 : Requêtes SQL traditionnelles avec clause `LIKE '%mot%'`
* **Avantages :** Ne requiert aucune configuration particulière.
* **Inconvénients :** Balayage séquentiel complet de la table (*full table scan*) très lent, aucune tolérance aux fautes d'orthographe, incapacité à associer « carotte » et « carottes ».

### Option 2 : Cluster Elasticsearch ou Meilisearch dédié
* **Avantages :** Référence de la recherche textuelle haute performance avec auto-complétion native.
* **Inconvénients :** Nécessite une machine virtuelle Java (JVM) consommant entre 1 et 2 Go de RAM rien qu'au démarrage, rupture de la cohérence transactionnelle ACID imposant un bus de synchronisation permanent entre PostgreSQL et Elasticsearch.

### Option 3 : PostgreSQL Full-Text Search (FTS) natif enrichi de l'extension `pg_trgm`
* **Avantages :** Intégré directement dans notre conteneur de base de données existant, dictionnaire linguistique `french` natif (lemmatisation et suppression des mots vides), tolérance aux fautes d'orthographe via le calcul de similarité trigramme (`pg_trgm`), indexation pondérée (`setweight`).
* **Inconvénients :** Syntaxe SQL légèrement plus technique (`to_tsvector`, `to_tsquery`).

---

## 3. Décision arbitrée

Nous retenons l'**Option 3 : PostgreSQL Full-Text Search avec l'extension trigramme `pg_trgm`**.

Une colonne générée `search_vector` regroupe le titre (poids A), les légumes associés (poids B) et la préparation (poids C). L'index GIN associé permet une recherche instantanée (< 5 ms) et l'autocomplétion tolère les fautes de frappe (ex. `courget` oriente vers `courgette`) sans ajouter aucun conteneur lourd supplémentaire.

---

## 4. Conséquences & Risques acceptés

* **Positives :**
  * Zéro empreinte mémoire additionnelle : valorisation maximale des capacités natives de PostgreSQL 16.
  * Cohérence transactionnelle immédiate : une nouvelle recette enregistrée en back-office est instantanément indexée et recherchable.
* **Négatives / Risques compensés :**
  * La recherche reste strictement lexicale : elle ne résout pas la similarité d'idées ou de concepts culinaires si les termes sont totalement distincts (besoin traité séparément par l'ADR 09).