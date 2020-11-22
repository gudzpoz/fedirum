<?php

use Flarum\Extend;
use Flarum\Frontend\Document;
use Fedirum\Fedirum\WebFinger;
use Fedirum\Fedirum\Inbox;
use Fedirum\Fedirum\Outbox;
use Fedirum\Fedirum\Post;
use Fedirum\Fedirum\Actor;
use Fedirum\Fedirum\Config;
use Fedirum\Fedirum\Notification\PostLikedBlueprint;
use Flarum\Post\Event\Posted;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js'),
    (new Extend\Notification())
        ->type(PostLikedBlueprint::class, PostSerializer::class, ['alert']),
    (new Extend\Frontend('forum'))
        ->content(function (Document $document) {
            $document->head[] = '<script>console.log("Hello, world!")</script>';
	}),
    (new Extend\Routes('forum'))
        ->get('/.well-known/webfinger', 'fedirum.webfinger', WebFinger::class),
    (new Extend\Csrf())->exemptPath(Config::INBOX_PATH),
    (new Extend\Routes('forum'))
        ->post(Config::INBOX_PATH, 'fedirum.inbox', Inbox::class),
    (new Extend\Routes('forum'))
        ->get(Config::OUTBOX_PATH . '/testpath', 'fedirum.outbox', Outbox::class),
    (new Extend\Event())
        ->listen(Posted::class, Post::class),
    (new Extend\Middleware('forum'))->add(Actor::class)
];

