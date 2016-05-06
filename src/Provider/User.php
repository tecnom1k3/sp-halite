<?php
namespace Acme\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Acme\Service\User as UserService;

class User implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['user.service'] = $app->share(function($app) {
             return new UserService;
        });
        $controllers = $app['controllers_factory'];
        $controllers->get('/', 'Acme\\Controller\\User::getList');

        return $controllers;
    }
}
