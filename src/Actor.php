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
use Flarum\Post\PostRepository;

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
    protected $posts;
    public function __construct(UserRepository $repo, PostRepository $postRepo) {
        $this->users = $repo;
        $this->posts = $postRepo;
    }
    protected function getInfo($username): ?User {
        $this->user = $this->users->findByIdentification($username);
        return $this->user;
    }
    public static function getActorLink($username): string {
        return Actor::getBaseLink() . Config::ACTOR_PATH . $username;
    }
    public static function getFollowersLink($username): string {
        return self::getActorLink($username) . '/followers';
    }

    private function getActorResponse($username): Response
    {
        $send = new Send();
        $data = array(
            '@context' => array(
                'https://www.w3.org/ns/activitystreams',
            ),
            'id' => $this->getActorLink($username),
            'actor' => $this->getActorLink($username),
            'type' => 'Person',
            'preferredUsername' => $username,
            'inbox' => Inbox::getInboxLink(),
            'outbox' => Outbox::getOutboxLink(),
            'followers' => self::getFollowersLink($username),
            'url' => self::getActorLink($username),
            'publicKey' => array(
                'id' => self::getActorLink($username) . '#main-key',
                'owner' => self::getActorLink($username),
                'publicKeyPem' => $send->getPublicKey()
            )
        );
        return new JsonResponse($data, 200, [
            'Content-Type' => ['application/activity+json'],
        ]);
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
        if(!$this->isActivityRequest($request)) {
            return $handler->handle($request);
        }

        $userRoute = Config::ACTOR_PATH;
        if (substr($currentRoute, 0, strlen($userRoute)) === $userRoute) {
            $username = substr($currentRoute, strlen($userRoute));
            if(strpos($username, "/") === false && $this->getInfo($username)) {
                return $this->getActorResponse($username);
            } else if(strpos($username, 'followers')) {
                return new JsonResponse([
                    "@context" => "https://www.w3.org/ns/activitystreams",
                    "attributedTo" => substr($currentRoute, 0, strlen($currentRoute) - strlen('/followers')),
                    "id" => $currentRoute,
                    "orderedItems" => [],
                    "totalItems" => 0,
                    "type" => "OrderedCollection"
                ], 200, [
                    'Content-Type' => ['application/activity+json'],
                ]);
            }
        } else if(preg_match('/\\/d\\/(\\d+)\\/(\\d+)/', $currentRoute, $match)) {
            $post = $this->posts->query()->where([
                'discussion_id' => (int)$match[1],
                'number' => (int)$match[2]
            ])->first();
            if($post) {
                return new JsonResponse(QueuedPost::getPostObject($post), 200, [
                    'Content-Type' => ['application/activity+json']
                ]);
            }
        }
        return $handler->handle($request);
    }
}
