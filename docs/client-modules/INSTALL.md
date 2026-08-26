# Installation

1. Copier les fichiers dans le projet Laravel.
2. Lancer:
   php artisan migrate
   php artisan config:clear
   php artisan cache:clear

3. Ajouter les aliases middleware si nécessaire.
4. Ajouter:
   require __DIR__.'/growth_ops.php';
   dans routes/web.php.

5. Enregistrer GrowthOpsServiceProvider si nécessaire.
6. Ajouter les commandes au scheduler:

- growth:data-quality hourly
- growth:health everyFiveMinutes
- growth:seo-sitemap daily
- growth:analytics-rollup hourly
- growth:video-health hourly

7. Vérifier les permissions Admin.
