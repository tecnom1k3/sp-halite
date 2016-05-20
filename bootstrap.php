<?php
require_once('vendor/autoload.php');

use Acme\Provider\User as UserProvider;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Dotenv\Dotenv;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;

$dotenv = new Dotenv(__DIR__);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_DATABASE']);

$dbOptions = [
    'driver' => 'pdo_mysql',
    'host' => getenv('DB_HOST'),
    'dbname' => getenv('DB_DATABASE'),
    'user' => getenv('DB_USER'),
    'password' => getenv('DB_PASS'),
];

$app = new Application;

$app['debug'] = true;

$app->register(new ServiceControllerServiceProvider);


$app['doctrine.entityManager'] = $app->share(function () use ($dbOptions) {
    $paths = ['src/Model'];

    /** @var Configuration $config */
    $config = Setup::createAnnotationMetadataConfiguration($paths, true);

    return EntityManager::create($dbOptions, $config);
});

$app->mount('/users', new UserProvider);

$app->run();
