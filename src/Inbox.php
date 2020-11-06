<?php

namespace Fedirum\Fedirum;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class Inbox extends Actor implements RequestHandlerInterface {
    public static function getInboxLink() {
        return Actor::getBaseLink() . Config::INBOX_PATH;
    }
    
    private function getInboxResponse($username): Response
    {
        $data = array(
            '@context' => array(
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1'
            ),
            'id' => $this->getActorLink($username),
            'type' => 'Person',
            'preferredUsername' => $username,
            'inbox' => Inbox::getInboxLink(),
            'outbox' => Outbox::getOutboxLink,
            'url' => $this->getActorLink($username)
        );
        return new JsonResponse($data);
    }

    public function handle(Request $request): Response
    {
        error_log($request->getBody());
        $json = json_decode($request->getBody());
        $actor = null;
        if(is_string($json->actor)) {
            $actor = $json->actor;
        } else if(is_object($json->actor)) {
            $actor = $json->actor->id;
        }
        $id = $json->id;
        $object = null;
        if(is_string($json->object)) {
            $object = $json->object;
        }

        if($json->type === 'Follow') {
            
        }
        return new HtmlResponse('<h1>Access denied .</h1>', 403);
    }
}
