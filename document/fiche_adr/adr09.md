# ADR 09 : Recherche sémantique et similarité culinaire par vecteurs (pgvector)

* **Date :** Octobre 2024
* **Statut :** Accepté
* **Auteurs :** Alyssia Frey, Asya Elik
* **Contexte technique :** Module Recettes innovantes & Lutte anti-gaspillage - Sujet C (SAÉ 5)

---

## 1. Contexte & Problématique

Pour fidéliser les adhérents et éviter le gaspillage alimentaire, le réseau Cocagne souhaite aider les consommateurs à cuisiner les légumes méconnus ou livrés en grande quantité (rutabagas, blettes, topinambours, panais).

La recherche textuelle lexicale (mot-à-mot) échoue à comprendre la proximité conceptuelle entre deux plats : une « potée aux choux » et un « mijoté de légumes d'hiver » ne partagent presque aucun mot commun, mais correspondent à la même intention culinaire. Le système doit pouvoir suggérer des recettes équivalentes par le sens.

---

## 2. Alternatives analysées

### Option 1 : API cloud externe d'embeddings (OpenAI text-embedding-3 / Cohere)
* **Avantages :** Modèles très performants, zéro maintenance locale.
* **Inconvénients :** Facturation récurrente à chaque appel, dépendance réseau critique à un service cloud propriétaire américain, non-respect de la souveraineté des données.

### Option 2 : Base de données vectorielle spécialisée dédiée (Qdrant / Milvus / Pinecone)
* **Avantages :** Optimisée pour des millions de vecteurs à l'échelle industrielle mondiale.
* **Inconvénients :** Architecture surdimensionnée pour notre catalogue de recettes, imposition d'un nouveau serveur conteneurisé lourd à orchestrer dans Docker.

### Option 3 : Modèle open source français local (`bge-small-fr`) et extension PostgreSQL `pgvector`
* **Avantages :** Modèle léger (130 Mo) s'exécutant localement sur simple processeur (CPU), génération de vecteurs de dimension 384, stockage et calcul de similarité cosinus directement dans la base PostgreSQL via l'index `HNSW` de l'extension `pgvector`.
* **Inconvénients :** Nécessite l'installation de l'extension `pgvector` dans l'image Docker de la base de données.

---

## 3. Décision arbitrée

Nous retenons l'**Option 3 : Modèle local Hugging Face `bge-small-fr` couplé à l'extension PostgreSQL `pgvector`**.

Lorsqu'une recette est créée en back-office, un événement Symfony génère son empreinte vectorielle de dimension 384. PostgreSQL stocke ce vecteur dans une colonne `embedding vector(384)`. L'index HNSW permet d'extraire les 5 recettes les plus proches sémantiquement en moins de 5 ms.

---

## 4. Conséquences & Risques acceptés

* **Positives :**
  * **Souveraineté et gratuité absolue :** Zéro euro d'abonnement et aucune donnée transmise à des tiers.
  * **Suggestions anti-gaspillage pertinentes :** Remplacement automatique d'un légume manquant par une recette aux profils gustatifs et de préparation équivalents.
  * **Infrastructure unifiée :** Données comptables, géospatiales (PostGIS), textuelles (FTS) et vectorielles (pgvector) résident toutes dans le même moteur PostgreSQL 16.
* **Négatives / Risques compensés :**
  * Légère latence (environ 80 à 150 ms sur CPU) lors de la création d'une recette pour générer son vecteur initial, prise en charge de manière transparente en tâche de fond asynchrone via Symfony Messenger (ADR 05).