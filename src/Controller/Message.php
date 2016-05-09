<?php
namespace Acme\Controller;

use Silex\Application;
use Acme\Service\Message as MessageService;
use Symfony\Component\HttpFoundation\Request;

class Message
{
    private $service;

    public function __construct(MessageService $service)
    {
        $this->service = $service;
    }

    public function getList($userId, Application $app)
    {
        $arrMessages = $this->service->getList($userId);
        return $app->json($arrMessages);
    }
 
    public function save($userId, Request $request, Application $app)
    {
	$parameters = json_decode($request->getContent(), true);

        $lastInsertId = $this->service->save($parameters['from'], $userId, $parameters['subject'], $parameters['message']);
	return $app->json(['messageId' => $lastInsertId]);
    }
}
