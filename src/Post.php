<?php

namespace Fedirum\Fedirum;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Flarum\Post\Event\Posted;

class Post
{
    protected $translator;
    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function handle(Posted $event) {
    }
}
