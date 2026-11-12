# Compte-rendu d'Implémentation : API REST Sécurisée (SAÉ 05)

## 1. Réalisations et Architecture

*   **Développement du CRUD Utilisateur :** Mise en place d'un `UtilisateurController` gérant l'intégralité des méthodes HTTP (GET, POST, PUT, DELETE) avec une stricte séparation des données via des groupes de sérialisation (`#[Groups(['api_read'])]`) pour garantir la protection des mots de passe.
*   **Sécurité et Authentification :** Intégration de `LexikJWTAuthenticationBundle`. Création du point d'entrée `/api/login_check` pour la génération des tokens JWT et sécurisation des routes par hiérarchie de rôles (`IS_AUTHENTICATED_FULLY` pour la lecture, `ROLE_ADMIN` pour l'écriture).
*   **Documentation Dynamique :** Déploiement de `NelmioApiDocBundle` offrant une interface Swagger directement testable. Implémentation des attributs `OpenApi\Attributes` (OA) pour décrire avec précision les Body JSON attendus, les paramètres d'URL et l'exhaustivité des codes de retour HTTP (200, 201, 204, 400, 401, 403, 404).

## 2. Problèmes Techniques Rencontrés et Résolutions

*   **Désynchronisation du cache de routage sous Docker :** Les nouvelles méthodes (notamment POST) renvoyaient des erreurs 404. 
    * *Résolution :* Forçage manuel de la suppression du cache en ligne de commande Windows (`Remove-Item -Recurse -Force var\cache\dev`).
*   **Volatilité de la configuration pgAdmin :** Les serveurs enregistrés étaient purgés à chaque redémarrage des conteneurs (`docker compose down`). 
    * *Résolution :* Édition du `docker-compose.yaml` pour attribuer un volume persistant physique (`pgadmin_data:/var/lib/pgadmin`) au service d'administration.
*   **Conflit de syntaxe (Annotations vs Attributs) :** Le Serializer et Nelmio renvoyaient des réponses vides ou des erreurs de classes introuvables. 
    * *Résolution :* Migration totale des anciens imports d'annotations vers les attributs natifs de PHP 8 (ex: `Nelmio\ApiDocBundle\Attribute\Model` et `Symfony\Component\Serializer\Attribute\Groups`).
*   **Rejet 400 sur la génération du Token JWT :** Le pare-feu rejetait la tentative de connexion Postman malgré des identifiants valides. 
    * *Résolution :* Ajustement strict du payload JSON pour utiliser la clé standard `username` imposée par le mécanisme `json_login` de Symfony, en remplacement de la clé `email`.