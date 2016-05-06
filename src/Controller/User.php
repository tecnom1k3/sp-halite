<?php
namespace Acme\Controller;

use Silex\Application;
use Acme\Service\User as UserService;
use Symfony\Component\HttpFoundation\Request;

class User
{
    public function getList(Request $request, Application $app)
    {
       
        $arrUsers = $app['user.service']->getList();
        return $app->json($arrUsers);
    }
}
