<?php

namespace Fedirum\Fedirum;

use Flarum\Post\Event\Posted;
use Illuminate\Contracts\Queue\Factory;

class Post {
    protected $queue;
    public function __construct(Factory $factory) {
        $this->queue = $factory->connection('fedirum.posting');
    }
    
    public function handle(Posted $event) {
        $this->queue->push(new QueuedPost($event));
    }
}
