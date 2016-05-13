<?php
require_once('vendor/autoload.php');

use Acme\Provider\User as UserProvider;
use Dotenv\Dotenv;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Acme\Service\Halite;

$dotenv = new Dotenv(__DIR__);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_DATABASE']);

$app = new Application;

$app['debug'] = true;

$app->register(new ServiceControllerServiceProvider);

$app->register(new DoctrineServiceProvider, [
    'db.options' => [
        'driver' => 'pdo_mysql',
        'host' => getenv('DB_HOST'),
        'dbname' => getenv('DB_DATABASE'),
        'user' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'),
    ]
]);

$app['service.halite'] = $app->share(function () use ($app) {
    return new Halite;
});

$app->mount('/users', new UserProvider);

$app->run();
