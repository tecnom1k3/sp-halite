<?php
namespace Acme\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Acme\Service\User as UserService;
use Acme\Controller\User as UserController;

class User implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['service.user'] = $app->share(function($app) {
             return new UserService;
        });

        $app['controller.user'] = $app->share(function() use ($app) {
            return new UserController($app['service.user']);
        });
        $controllers = $app['controllers_factory'];
        $controllers->get('/', 'controller.user:getList');

        return $controllers;
    }
}
