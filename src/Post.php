<?php

namespace Fedirum\Fedirum;

use Flarum\Post\Event\Posted;
use Illuminate\Contracts\Queue\Factory;
use Laminas\Diactoros\Response\EmptyResponse;
use Illuminate\Contracts\Queue\Queue;

class Post {
    public function handle(Posted $event) {
        $queue = app(Queue::class);
        $queue->push(new QueuedPost($event));
        return new EmptyResponse(201);
    }
}
