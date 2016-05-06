<?php
namespace Acme\Controller;

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
        $controllers->get('/', function (Application $app) {
            $arrUsers = $app['user.service']->getList();
            return $app->json($arrUsers);
        });

        return $controllers;
    }
}
