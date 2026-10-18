# ADR 03 : Choix du cadriciel backend applicatif (Symfony 7.4 LTS vs Concurrents)

* **Date :** Octobre 2024
* **Statut :** Accepté
* **Auteurs :** Alyssia Frey, Asya Elik
* **Contexte technique :** Noyau applicatif serveur (SAÉ 5)

---

## 1. Contexte & Problématique

Le serveur applicatif doit orchestrer une logique métier dense :
* Algorithmes d'optimisation combinatoire logistique (CVRP, Clarke & Wright, 2-opt).
* Traitement asynchrone des tâches lourdes (calculs de routes, génération de feuilles d'émargement et d'étiquettes).
* Ségrégation stricte des données entre une centaine de structures maraîchères indépendantes.

Le framework doit garantir une maintenabilité à long terme, une typologie stricte et une compatibilité avec les standards d'évaluation académiques.

---

## 2. Alternatives analysées

### Option 1 : Laravel 11
* **Avantages :** Démarrage rapide, écosystème riche de packages, syntaxe expressive.
* **Inconvénients :** Recours fréquent aux façades statiques, ORM Eloquent reposant sur le patron *Active Record* qui couple fortement les entités de domaine à la base de données, rendant l'isolation multi-tenant plus fragile.

### Option 2 : NestJS / Express (Node.js)
* **Avantages :** Unification du langage (TypeScript) sur l'ensemble de la pile, modèle asynchrone par événements performant en entrées/sorties.
* **Inconvénients :** Écosystème ORM (Prisma / TypeORM) moins mature pour la gestion fine des verrous transactionnels pessimistes (`SELECT ... FOR UPDATE`) et des extensions spatiales PostGIS complexes.

### Option 3 : Django (Python)
* **Avantages :** Robustesse d'entreprise, puissant pour le traitement de données et l'apprentissage automatique.
* **Inconvénients :** Éloigné de l'historique technique de l'équipe et des standards d'évaluation du cursus.

### Option 4 : Symfony 7.4 LTS (PHP 8.3)
* **Avantages :** Patron d'architecture en services stricts (injection de dépendances), ORM Doctrine en patron *Data Mapper* découplant le modèle métier de la persistance, bus de messages natif (Symfony Messenger), support officiel à long terme garanti pendant 3 ans.

---

## 3. Décision arbitrée

Nous retenons l'**Option 4 : Symfony 7.4 LTS adossé à PHP 8.3**.

Symfony offre la rigueur architecturale indispensable à une application de gestion mutualisée complexe. Doctrine permet d'intercepter toutes les requêtes via des filtres SQL globaux, et Symfony Messenger gère nativement l'exécution asynchrone des calculs de tournées.

---

## 4. Conséquences & Risques acceptés

* **Positives :**
  * Robustesse logicielle appuyée sur le typage strict (`declare(strict_types=1);`) et les énumérations natives de PHP 8.3.
  * Découplage complet entre contrôleurs HTTP et logique métier via les services de domaine (`Delivery`, `Geo`, `Recipe`).
  * Pérennité assurée par le cycle de publication prévisible de la version LTS.
* **Négatives / Risques compensés :**
  * Configuration initiale plus rigoureuse et verbeuse qu'avec un micro-framework, compensée par l'utilisation des générateurs MakerBundle.