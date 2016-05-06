<?php
chdir(dirname(__DIR__));
require_once('vendor/autoload.php');

use Silex\Application;
use Acme\Controller\User as UserController;

$app = new Application;

$app->mount('/users', new UserController);

$app->run();
