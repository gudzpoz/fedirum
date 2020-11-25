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
use Flarum\Tags\TagRepository;
use Flarum\Tags\Tag;

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
    protected $tags;
    public function __construct(UserRepository $repo, PostRepository $postRepo, TagRepository $tags) {
        $this->users = $repo;
        $this->posts = $postRepo;
        $this->tags = $tags;
    }
    protected function getInfo($username): ?User {
        $this->user = $this->users->findByIdentification($username);
        return $this->user;
    }
    public static function getActorLink($username): string {
        return Actor::getBaseLink() . Config::ACTOR_PATH . $username;
    }
    
    public static function getTagLink($tagname): string {
        return Actor::getBaseLink() . Config::TAG_PATH . $tagname;
    }
    
    public static function getFollowersLink($username): string {
        return self::getActorLink($username) . '/followers';
    }

    private function getTagResponse($tagname): Response {
        $id = $this->tags->getIdForSlug($tagname);
        $tag = null;
        if(is_int($id)) {
            try {
                $tag = $this->tags->findOrFail($id);
            } catch (Exception $e) {
                return new HtmlResponse('<h1>No such tag.</h1>', 404);
            }
        } else {
            return new HtmlResponse('<h1>No such tag.</h1>', 404);
        }
        
        $send = new Send();
        $data = array(
            '@context' => array(
                'https://www.w3.org/ns/activitystreams',
            ),
            'id' => $this->getTagLink($tagname),
            'actor' => $this->getTagLink($tagname),
            'type' => 'Group',
            'name' => $tag->name,
            'preferredUsername' => $tag->name,
            'inbox' => Inbox::getInboxLink(),
            'outbox' => Outbox::getOutboxLink(),
            'followers' => this::getTagLink($tagname) . '/followers',
            'url' => self::getTagLink($username),
            'publicKey' => array(
                'id' => self::getTagLink($username) . '#main-key',
                'owner' => self::getTagLink($username),
                'publicKeyPem' => $send->getPublicKey()
            )
        );
        return new JsonResponse($data, 200, [
            'Content-Type' => ['application/activity+json'],
        ]);
    }

    private function getActorResponse($username): Response
    {
        $user = $this->getInfo($username);
        if(!$user) {
            return new HtmlResponse('<h1>No user found.</h1>', 404);
        }
        
        $send = new Send();

        $icon = null;
        if($user->avatar_url) {
            $icon = [
                'type' => 'Image',
                'url' => $user->avatar_url
            ];
        }
        $data = array(
            '@context' => array(
                'https://www.w3.org/ns/activitystreams',
            ),
            'id' => $this->getActorLink($username),
            'actor' => $this->getActorLink($username),
            'type' => 'Person',
            'name' => $user->display_name,
            'icon' => $icon,
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
        if(substr($currentRoute, 0, strlen(Config::TAG_PATH)) === Config::TAG_PATH) {
            $tag = substr($currentRoute, strlen($userRoute));
            return $this->getTagResponse($tag);
        } else if(substr($currentRoute, 0, strlen($userRoute)) === $userRoute) {
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
        } else {
            $match = QueuedPost::parsePostPath($currentRoute);
            if($match) {
                $post = $this->posts->query()->where([
                    'discussion_id' => $match[0],
                    'number' => $match[1]
                ])->first();
                if($post) {
                    return new JsonResponse(QueuedPost::getPostObject($post), 200, [
                        'Content-Type' => ['application/activity+json']
                    ]);
                }
            }
        }
        return $handler->handle($request);
    }
}
