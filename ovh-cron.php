<?php

/*
|--------------------------------------------------------------------------
| Entry point for the OVH scheduled task ("Tâche planifiée - Cron")
|--------------------------------------------------------------------------
|
| OVH web hosting runs a PHP *file*, not a shell command, at most once an
| hour. In the OVH Control Panel: Hébergements > your hosting > Tâches
| planifiées - Cron > Ajouter, then:
|
|   Commande à exécuter : <project folder>/ovh-cron.php   (e.g. shop-laravel/ovh-cron.php)
|   Langage             : PHP 8.5 (same version as .ovhconfig)
|   Fréquence           : toutes les heures
|
| It runs Laravel's scheduler (routes/console.php).
|
*/

use Symfony\Component\Console\Input\ArgvInput;

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

exit($app->handleCommand(new ArgvInput(['artisan', 'schedule:run'])));
