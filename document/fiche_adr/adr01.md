# ADR 01 : Choix du modèle de restitution web (Hybride Symfony UX vs SPA)

* **Date :** Octobre 2024
* **Statut :** Accepté
* **Auteurs :** Alyssia Frey, Asya Elik
* **Contexte technique :** Projet de gestion commerciale et logistique des Jardins de Cocagne (SAÉ 5)

---

## 1. Contexte & Problématique

L'application web mutualisée des Jardins de Cocagne doit servir deux populations distinctes :
1. Les adhérents du réseau (consultation des paniers, calendrier des distributions, recherche de recettes culinaires).
2. Les équipes encadrantes et chauffeurs-livreurs (gestion des tournées sur carte interactive, planification, émargement mobile).

L'interface nécessite une forte réactivité locale (manipulation d'une carte Leaflet et glisser-déposer sur FullCalendar), tout en restant maintenable par une structure associative ne disposant pas d'une équipe dédiée de spécialistes JavaScript frontend.

---

## 2. Alternatives analysées

### Option 1 : SPA découplée intégrale (React / Vite + API REST Symfony)
* **Avantages :** Fluidité maximale des transitions côté client, écosystème de composants riche.
* **Inconvénients :** Deux bases de code séparées à maintenir, gestion complexe de l'authentification par jetons JWT, duplication de la logique de validation des formulaires, coût d'intégration et de montée en compétences trop élevé pour le réseau Cocagne.

### Option 2 : Rendu serveur classique pur (Twig sans JavaScript dynamique)
* **Avantages :** Simplicité architecturale, sécurité native contre les failles XSS, gestion de session centralisée par cookies sécurisés.
* **Inconvénients :** Rechargement complet de la page à chaque interaction, rendant inexploitable la manipulation fluide de la carte de livraison et du calendrier des tournées.

### Option 3 : Modèle hybride rendu serveur et micro-contrôleurs (Twig + Symfony UX / Stimulus & Turbo)
* **Avantages :** Rendu HTML côté serveur sécurisé et rapide, complété par des micro-contrôleurs JavaScript modulaires (Stimulus) pour encapsuler Leaflet et FullCalendar sans surcoût de bundling SPA.
* **Inconvénients :** Nécessite une bonne structuration des événements DOM pour la communication entre contrôleurs.

---

## 3. Décision arbitrée

Nous retenons l'**Option 3 : Modèle hybride (Twig + Symfony UX / Stimulus & Turbo)**.

Le rendu serveur natif avec Twig garantit une sécurité stricte et une maintenance allégée. Les contrôleurs Stimulus permettent d'isoler le comportement dynamique de la carte et du calendrier sans éclater l'architecture en deux applications indépendantes.

---

## 4. Conséquences & Risques acceptés

* **Positives :**
  * Architecture unifiée au sein d'un seul codebase Symfony.
  * Absence de dépendance lourde à un pipeline de compilation complexe en production (compatible avec Symfony AssetMapper).
  * Ergonomie réactive pour la manipulation des tournées sans latence de rechargement complet.
* **Négatives / Risques compensés :**
  * Nécessite de concevoir des endpoints API JSON dédiés (`/api/tournees/...`) spécifiquement pour alimenter les contrôleurs Stimulus.