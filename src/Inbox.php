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
            if($object && $actor && is_string($id)) {
                $ship = new Followship();
                $ship->follower = $actor;

                $name = Actor::getUserName($object);
                if($name) {
                    $user = $this->getInfo($name);
                    if($user) {
                        $sender = new Send();
                        if(!$sender->verify(request)) {
                            return new HtmlResponse('<h1>Illegal Authentication.</h1>');
                        }
                        
                        $ship->id = $user->id;
                        $oldShip = Followship::where('id', $ship->id)
                            ->where('follower', $ship->follower)
                            ->first();
                        $response = $sender->get($ship->follower);
                        $inbox = json_decode($response->getBody())->inbox;
                        if(is_string($inbox)) {
                            $ship->inbox = $inbox;
                        }
                        if($oldShip) {
                            $oldShip->inbox = $ship->inbox;
                            $oldShip->save();
                        } else {
                            $ship->save();
                        }
                        $content = json_encode([
                            '@content' => 'https://www.w3.org/ns/activitystreams',
                            'actor' => $object,
                            'type' => 'Accept',
                            'object' => $id
                        ]);
                        # TODO: Implement requist verification before Accept
                        # $sender->post($name, $content, $ship->inbox);
                        error_log($content);
                        return new JsonResponse([
                            'type' => 'Accept',
                            'object' => $id
                        ]);
                    }
                }
            }
        }
        return new HtmlResponse('<h1>Access denied .</h1>', 403);
    }
}
