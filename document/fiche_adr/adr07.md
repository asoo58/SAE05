# ADR 07 : Moteur de calcul d'itinéraires et métriques routières

* **Date :** Octobre 2024
* **Statut :** Accepté
* **Auteurs :** Alyssia Frey, Asya Elik
* **Contexte technique :** Module Tournées & Heuristiques - Sujet B (SAÉ 5)

---

## 1. Contexte & Problématique

Pour construire des tournées de livraison cohérentes (ex. dans le bassin vosgien autour de Thaon-les-Vosges), le système doit connaître la distance kilométrique exacte et le temps de trajet réel entre chaque paire de points de dépôt.

Dans les zones rurales ou accidentées, le calcul de distance à vol d'oiseau est totalement inopérant car il ignore le relief, les rivières et le tracé réel des routes secondaires.

---

## 2. Alternatives analysées

### Option 1 : Calcul de distance géométrique euclidien ou Haversine (vol d'oiseau)
* **Avantages :** Calcul instantané en mémoire sans aucune requête réseau.
* **Inconvénients :** Erreur d'estimation de 30 à 60 % dans les zones montagneuses ou semi-rurales, provoquant des feuilles de route irréalistes pour les chauffeurs.

### Option 2 : API de routage SaaS externe (Google Distance Matrix / GraphHopper API)
* **Avantages :** Prise en compte du trafic en direct, aucun serveur à maintenir.
* **Inconvénients :** Facturation récurrente à chaque calcul de matrice pour l'algorithme de Clarke & Wright ($O(N^2)$ requêtes), quotas stricts risquant de bloquer l'application en cours de tournée.

### Option 3 : Open Source Routing Machine (OSRM) auto-hébergé sous Docker
* **Avantages :** Moteur libre ultra-rapide en C++, calcul de matrices de distances en quelques millisecondes, génération des tracés vectoriels exacts en GeoJSON, indépendance totale vis-à-vis d'un tiers payant.
* **Inconvénients :** Nécessite une étape de prétraitement des données routières OpenStreetMap (`osrm-extract`, `osrm-contract`) lors du déploiement initial.

---

## 3. Décision arbitrée

Nous retenons l'**Option 3 : Moteur OSRM (Open Source Routing Machine) conteneurisé**.

OSRM est instancié comme un service local dans notre architecture Docker. Le backend Symfony interroge l'API HTTP locale d'OSRM pour récupérer la matrice des temps et distances réelles pour nos algorithmes combinatoires, ainsi que la polyligne vectorielle restituée sur la carte Leaflet.

---

## 4. Conséquences & Risques acceptés

* **Positives :**
  * Calcul de matrice de tournées quasi-instantané (moins de 50 ms pour 15 dépôts).
  * Feuilles de route précises avec kilométrage et durées de conduite réalistes.
  * Zéro coût d'API récurrent.
* **Négatives / Risques compensés :**
  * Consommation d'environ 500 Mo de RAM dédiée dans Docker pour charger en mémoire le graphe routier de la région cible (Lorraine / Grand Est).