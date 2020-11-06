<?php

namespace Fedirum\Fedirum;

use Laminas\Diactoros\Uri;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flarum\User\UserRepository;
use Flarum\User\User;

class Actor implements MiddlewareInterface {
    public static function getBaseLink(): string {
        return 'https://' . $_SERVER['SERVER_NAME'];
    }
    public static function getUserName(string $link): ?string {
        $path = parse_url($link, PHP_URL_PATH);
        $pos = strrpos($path, '/');
        if($pos !== false) {
            return substr($path, $pos + 1);
        } else {
            return null;
        }
    }
    
    private $user;
    protected $users;
    public function __construct(UserRepository $repo) {
        $this->users = $repo;
    }
    protected function getInfo($username): ?User {
        $this->user = $this->users->findByIdentification($username);
        return $this->user;
    }
    public static function getActorLink($username): string {
        return Actor::getBaseLink() . Config::ACTOR_PATH . $username;
    }

    private function getActorResponse($username): Response
    {
        $send = new Send();
        $data = array(
            '@context' => array(
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1'
            ),
            'id' => $this->getActorLink($username),
            'type' => 'Person',
            'preferredUsername' => $username,
            'inbox' => Inbox::getInboxLink(),
            'outbox' => Outbox::getOutboxLink(),
            'url' => self::getActorLink($username),
            'publicKey' => array(
                'id' => self::getActorLink($username) . '#main-key',
                'owner' => self::getActorLink($username),
                'publicKeyPem' => $send->getPublicKey()
            )
        );
        return new JsonResponse($data);
    }

    protected function isActivityRequest(Request $request) {
        $headers = $request->getHeaders();
        if($headers && array_key_exists('accept', $headers)) {
            foreach($headers['accept'] as $accept) {
                if(strpos($accept, 'application/activity+json') !== false
                   || strpos($accept, 'application/ld+json; profile="https://www.w3.org/ns/activitystreams') !== false
                   || strpos($accept, 'application/ld+json') !== false) {
                    return TRUE;
                }
            }
        }
        
        return FALSE;
    }
    
    public function process(Request $request, RequestHandlerInterface $handler): Response {
        $currentRoute = $request->getUri()->getPath();

        $userRoute = Config::ACTOR_PATH;
        if($this->isActivityRequest($request)) {
            if (substr($currentRoute, 0, strlen($userRoute)) === $userRoute) {
                $username = substr($currentRoute, strlen($userRoute));
                if(strpos($username, "/") === false && $this->getInfo($username)) {
                    return $this->getActorResponse($username);
                }
            }
        }
        return $handler->handle($request);
    }
}
