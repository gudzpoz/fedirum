<?php

namespace Fedirum\Fedirum;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request as GHRequest;
use GuzzleHttp\Exception\RequestException;

class Outbox extends Actor implements RequestHandlerInterface {
    public static function getOutboxLink() {
        return Actor::getBaseLink() . Config::OUTBOX_PATH;
    }
    
    private function getOutboxResponse($username): Response
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
            'outbox' => OutBox::getOutboxLink(),
            'url' => $this->getActorLink($username)
        );
        return new JsonResponse($data);
    }

    public function handle(Request $request): Response
    {
        $content = <<<EOD
{
    "@context": "https://www.w3.org/ns/activitystreams",
    "id": "https://lemmy.exopla.net.eu.org/create-hello-world",
    "type": "Create",
    "actor": "https://lemmy.exopla.net.eu.org/fedirum/user/alien",
    "object": {
        "id": "https://lemmy.exopla.net.eu.org/hello-world",
        "type": "Note",
        "published": "2018-06-23T17:17:11Z",
        "attributedTo": "https://lemmy.exopla.net.eu.org/fedirum/user/alien",
        "inReplyTo": "https://mastodon.social/@Gargron/100254678717223630",
        "content": "<p>testing... (sorry for disturbing and thank you for the tutorial :P )</p>",
        "to": "https://www.w3.org/ns/activitystreams#Public"
    }
}
EOD;
        $send = new Send();
        $send->send($user, $content, 'https://mastodon.social/inbox');
        return new HtmlResponse('<h1>Access denied.</h1>', 403);
    }
}
