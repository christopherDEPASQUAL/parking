# Parking

Environnement Docker avec PHP 8.3, Composer 2, PHPUnit 12, MySQL (app + tests) et PhpMyAdmin.

## Prerequis
- Docker Desktop (ou equivalent) avec Docker Compose plugin.

## Lancer l'infrastructure
1. Construire/zapper les images : `docker compose build`
2. Demarrer les services (PHP, MySQL app/test, PhpMyAdmin) : `docker compose up -d`
3. PhpMyAdmin est disponible sur http://localhost:8080 (hote `app_db`, user `parking`, mot de passe `secret`).
4. Deux bases MySQL sont initialisees automatiquement :
   - `parking` (application) depuis `infrastructure/mysql/app/init.sql`
   - `parking_test` (tests) depuis `infrastructure/mysql/test/init.sql`
5. Le stockage alternatif JSON (pour demontrer l'interchangeabilite des depots) ecrit dans `storage/users.json`. Le chemin est expose via la variable d'env `JSON_USER_STORAGE` dans `docker-compose.yml`.

## Modes de persistance (SQL / JSON)

- Choix du driver via `PERSISTENCE_DRIVER=sql|json` (par defaut `json` pour la demo hors base).
- Chemins de stockage JSON configurables : `JSON_USER_STORAGE`, `JSON_PARKING_STORAGE`, `JSON_RESERVATION_STORAGE` (par defaut sous `storage/*.json`).
- Script de reset des fichiers JSON : `php bin/reset-json-storage.php`.
- Limitations mode JSON : disponibilite calculee sans contexte reservation/abonnement (capacite brute) et revenus retournes a 0.
- Tests d'integration :
  - JSON : `./vendor/bin/phpunit --testsuite Integration --filter JsonPersistenceTest`
  - SQL (optionnel) : `RUN_SQL_INTEGRATION=1 PERSISTENCE_DRIVER=sql ./vendor/bin/phpunit --testsuite Integration --filter SqlPersistenceTest` (necessite la base en route).

## Commandes utiles
- Installer les dependances : `docker compose run --rm app composer install`
- Mettre a jour l'autoload : `docker compose run --rm app composer dump-autoload`
- Lancer tous les tests : `docker compose run --rm app ./vendor/bin/phpunit`
- Lancer une suite : `docker compose run --rm app ./vendor/bin/phpunit --testsuite Unit` (ou `Integration`, `Functional`)
- Couverture (Xdebug) en texte : `docker compose run --rm -e XDEBUG_MODE=coverage app ./vendor/bin/phpunit --coverage-text`
- Couverture HTML : `docker compose run --rm -e XDEBUG_MODE=coverage app ./vendor/bin/phpunit --coverage-html coverage` puis ouvrir `coverage/index.html`
- Ouvrir un shell dans le conteneur : `docker compose run --rm app bash`
- (Optionnel) serveur PHP interne : `docker compose run --rm --service-ports app php -S 0.0.0.0:8000 -t public`
