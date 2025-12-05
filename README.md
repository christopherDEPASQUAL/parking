# Parking

Environnement Docker avec PHP 8.3, Composer 2 et PHPUnit 12.

## Prerequis
- Docker Desktop (ou equivalent) avec Docker Compose plugin.

## Commandes utiles
- Construire l'image : `docker compose build`
- Installer les dependances : `docker compose run --rm app composer install`
- Mettre a jour l'autoload : `docker compose run --rm app composer dump-autoload`
- Lancer tous les tests : `docker compose run --rm app ./vendor/bin/phpunit`
- Lancer une suite : `docker compose run --rm app ./vendor/bin/phpunit --testsuite Unit` (ou `Integration`, `Functional`)
- Couverture (Xdebug) en texte : `docker compose run --rm -e XDEBUG_MODE=coverage app ./vendor/bin/phpunit --coverage-text`
- Couverture HTML : `docker compose run --rm -e XDEBUG_MODE=coverage app ./vendor/bin/phpunit --coverage-html coverage` puis ouvrir `coverage/index.html`
- Ouvrir un shell dans le conteneur : `docker compose run --rm app bash`
- (Optionnel) serveur PHP interne : `docker compose run --rm --service-ports app php -S 0.0.0.0:8000 -t public` (adapte `-t` selon ton dossier public)
