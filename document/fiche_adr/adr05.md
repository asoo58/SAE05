# ADR 05 : Traitement asynchrone des calculs logistiques et éditions documentaires

* **Date :** Octobre 2024
* **Statut :** Accepté
* **Auteurs :** Alyssia Frey, Asya Elik
* **Contexte technique :** Performance et exécution des batchs lourds (SAÉ 5)

---

## 1. Contexte & Problématique

L'organisation des distributions maraîchères impose des opérations de calcul et d'édition particulièrement volumineuses :
* Optimisation combinatoire des tournées de livraison sur plusieurs dépôts.
* Génération vectorielle des trois documents obligatoires des chantiers d'insertion (feuilles de préparation, étiquettes cagettes nominatives et feuilles de route chauffeurs).

Exécuter ces traitements de manière synchrone lors d'une requête HTTP bloquerait le thread PHP de l'utilisateur et provoquerait des erreurs d'expiration de délai (HTTP 504 Gateway Timeout).

---

## 2. Alternatives analysées

### Option 1 : Traitement synchrone HTTP direct
* **Avantages :** Très simple à coder (aucun composant de file d'attente).
* **Inconvénients :** Navigation bloquée pour les gestionnaires, risque de plantage navigateur lors de la génération de centaines d'étiquettes PDF.

### Option 2 : Déploiement d'un broker de messages dédié (RabbitMQ ou Redis)
* **Avantages :** Débit massif de messages, référence des architectures orientées événements.
* **Inconvénients :** Nécessite d'ajouter un conteneur serveur supplémentaire sous Docker, augmentant la complexité d'exploitation et la consommation mémoire du serveur hôte.

### Option 3 : Symfony Messenger adossé au transport natif PostgreSQL (`doctrine://default`)
* **Avantages :** Composant officiel intégré à Symfony, stockage des messages dans une table dédiée de la base PostgreSQL existante, acquittement HTTP immédiat (code 202), aucun service serveur supplémentaire à maintenir.
* **Inconvénients :** Débit maximal inférieur à RabbitMQ (largement suffisant pour l'échelle de 100 structures maraîchères).

---

## 3. Décision arbitrée

Nous retenons l'**Option 3 : Symfony Messenger avec transport Doctrine / PostgreSQL**.

Lorsqu'un gestionnaire demande l'optimisation d'une tournée ou l'édition des étiquettes d'une distribution, le contrôleur Symfony émet un message typé (ex. `GenerateRoadmapMessage`) dans la file d'attente PostgreSQL et renvoie immédiatement une réponse HTTP `202 Accepted`. Un worker CLI d'arrière-plan traite les messages en tâche de fond.

---

## 4. Conséquences & Risques acceptés

* **Positives :**
  * Temps de réponse transactionnel inférieur à 200 ms pour l'utilisateur.
  * Zéro conteneur tiers supplémentaire dans l'architecture Docker.
  * Tolérance aux pannes : en cas d'erreur de rendu, le message est réessayé automatiquement grâce au mécanisme de retry paramétrable de Messenger.
* **Négatives / Risques compensés :**
  * Nécessite de configurer et superviser un processus d'arrière-plan (`php bin/console messenger:consume`) dans le conteneur applicatif.