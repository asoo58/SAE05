# ADR 02 : Choix du moteur de persistance relationnelle et géospatiale

* **Date :** Octobre 2024
* **Statut :** Accepté
* **Auteurs :** Alyssia Frey, Asya Elik
* **Contexte technique :** Socle de persistance de données (SAÉ 5)

---

## 1. Contexte & Problématique

L'application des Jardins de Cocagne doit centraliser des données relationnelles strictes (adhésions, abonnements paniers, facturation, quotas de dépôts) soumises à des contraintes comptables et transactionnelles fortes (normes SEPA, loi anti-fraude). 

Simultanément, le Sujet B impose le stockage des coordonnées géographiques des points de dépôts (PDD) et l'exécution de requêtes de proximité spatiale (ex. trouver les dépôts situés à moins de 10 km du domicile d'un adhérent).

---

## 2. Alternatives analysées

### Option 1 : MySQL 8.0
* **Avantages :** Très répandu, intégration simple avec Doctrine ORM.
* **Inconvénients :** Fonctions géospatiales limitées et moins précises que PostGIS, analyseurs de recherche plein texte en langue française peu évolués, absence de support vectoriel natif pour les évolutions IA.

### Option 2 : MongoDB (NoSQL orienté documents)
* **Avantages :** Flexibilité du schéma JSON pour modéliser des paniers et recettes hétérogènes.
* **Inconvénients :** Inadapté aux contraintes transactionnelles ACID strictes nécessaires à la facturation associative, risque d'incohérence sur les réservations de paniers et les plafonds de dépôts.

### Option 3 : PostgreSQL 16 avec extension PostGIS
* **Avantages :** Référence industrielle des SGBD relationnels et spatiaux, support natif du standard OGC (types `GEOMETRY` et index `GIST`), garanties ACID strictes, extensions natives complémentaires pour la recherche plein texte (`pg_trgm`, `FTS`) et vectorielle (`pgvector`).
* **Inconvénients :** Empreinte mémoire légèrement supérieure à MySQL en environnement conteneurisé minimal.

---

## 3. Décision arbitrée

Nous retenons l'**Option 3 : PostgreSQL 16 enrichi de l'extension spatiale PostGIS**.

PostgreSQL permet de consolider 100 % des besoins de persistance (données comptables, géolocalisation des dépôts en coordonnées WGS 84 SRID 4326, indexation textuelle) au sein d'un unique service de base de données.

---

## 4. Conséquences & Risques acceptés

* **Positives :**
  * Intégrité référentielle et transactionnelle absolue pour la gestion des adhérents et des tournées.
  * Requêtage spatial haute performance via la fonction `ST_DWithin` adossée à un index spatial `GIST`.
  * Économie substantielle de ressources : une seule instance de base de données à administrer, sauvegarder et superviser dans Docker.
* **Négatives / Risques compensés :**
  * Nécessite l'installation des bibliothèques C `libpq` et `geos` au niveau de l'image Docker de runtime.