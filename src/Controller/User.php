<?php
namespace Acme\Controller;

use Silex\Application;
use Acme\Service\User as UserService;
use Symfony\Component\HttpFoundation\Request;

class User
{
    private $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function getList(Request $request, Application $app)
    {
       
        $arrUsers = $this->service->getList();
        return $app->json($arrUsers);
    }
}
