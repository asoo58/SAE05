# ADR 06 : Bibliothèque de cartographie interactive et données géographiques

* **Date :** Octobre 2024
* **Statut :** Accepté
* **Auteurs :** Alyssia Frey, Asya Elik
* **Contexte technique :** Module Cartographie & Tournées - Sujet B (SAÉ 5)

---

## 1. Contexte & Problématique

Le module logistique (Sujet B) exige de tracer sur une carte interactive les itinéraires des camionnettes de livraison en reliant le Jardin maraîcher aux différents points de dépôt (PDD) sélectionnés.

La solution cartographique doit être légère, compatible avec les écrans de back-office et mobile, et conforme aux valeurs éthiques et d'économie de moyens du réseau associatif Cocagne.

---

## 2. Alternatives analysées

### Option 1 : Google Maps JavaScript API
* **Avantages :** Rendu très familier pour le grand public, fonds satellites de haute qualité.
* **Inconvénients :** Nécessite une carte bancaire et un compte GCP, tarification à l'usage prohibitive dès que le volume de requêtes augmente, API propriétaire incompatible avec les valeurs de souveraineté numérique du Réseau Cocagne.

### Option 2 : Mapbox GL JS
* **Avantages :** Rendu vectoriel accéléré par WebGL fluide et esthétique.
* **Inconvénients :** Licence commerciale restreinte imposant un modèle freemium avec clé d'API obligatoire et quotas stricts.

### Option 3 : Leaflet.js adossé aux tuiles OpenStreetMap (OSM)
* **Avantages :** Bibliothèque open source ultra-légère (environ 42 Ko), totalement gratuite, sans abonnement ni clé d'API propriétaire, supportant nativement les couches GeoJSON et les tuiles souveraines d'OpenStreetMap.
* **Inconvénients :** Rendu matriciel (raster) par défaut, nécessitant des optimisations manuelles en cas d'affichage simultané de milliers de marqueurs.

---

## 3. Décision arbitrée

Nous retenons l'**Option 3 : Leaflet.js couplé aux tuiles OpenStreetMap**.

Cette solution open source garantit une indépendance totale vis-à-vis des géants du web tout en assurant une intégration modulaire parfaite dans nos contrôleurs JavaScript Stimulus.

---

## 4. Conséquences & Risques acceptés

* **Positives :**
  * Coût financier d'exploitation nul pour le réseau associatif.
  * Empreinte réseau minimale sur les postes de travail des encadrants maraîchers.
  * Personnalisation complète des icônes de dépôts (couleurs différenciées selon la catégorie : ouvert à tous, réservé ou professionnel).
* **Négatives / Risques compensés :**
  * Respect nécessaire des conditions d'utilisation des serveurs de tuiles publics d'OpenStreetMap (mise en cache des tuiles par le navigateur et volume de requêtes modéré).