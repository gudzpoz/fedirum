<?php

namespace Fedirum\Fedirum;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class WebFinger implements RequestHandlerInterface
{
    private function getWebfingerResponse($resource, $username): Response
    {
        $data = array(
            'subject' => $resource,
            'links' => array(
                array(
                    'rel' => 'self',
                    'type' => 'application/activity+json',
                    'href' => 'https://' . $_SERVER['SERVER_NAME'] . Config::ACTOR_PATH . $username
                ),
                array(
                    'rel' => 'self',
                    'type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                    'href' => 'https://' . $_SERVER['SERVER_NAME'] . Config::ACTOR_PATH . $username
                )
            )
        );
        return new JsonResponse($data);
    }

    private function getWebfingerResponse($resource, $username): Response
    {
        $data = array(
            'subject' => $resource,
            'links' => array(
                array(
                    'rel' => 'self',
                    'type' => 'application/activity+json',
                    'href' => 'https://' . $_SERVER['SERVER_NAME'] . Config::TAG_PATH . $username
                ),
                array(
                    'rel' => 'self',
                    'type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                    'href' => 'https://' . $_SERVER['SERVER_NAME'] . Config::TAG_PATH . $username
                )
            )
        );
        return new JsonResponse($data);
    }

    public function handle(Request $request): Response
    {
        $resource = $request->getQueryParams()['resource'];
        if(preg_match('/^acct:([^@]+)(@.+)$/i', $resource, $matches))
        {
            if($matches[2] == '@' . $_SERVER['SERVER_NAME']) {
                if($matches[1][0] === '!') {
                    return $this->getWebfingerTag($resource, substr($matches[1], 1));
                } else {
                    return $this->getWebfingerResponse($resource, $matches[1]);
                }
            }
            else {
                return new HtmlResponse('<h1>404 Not Found</h1>', 404);
            }
        }
        else
        {
            return new HtmlResponse('<h1>Invalid Params! </h1>', 404);
        }
    }
}
