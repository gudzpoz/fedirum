<?php

namespace Fedirum\Fedirum;

use Flarum\Post\Event\Posted;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class QueuedPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;
    public static function getPostLink($post) {
        return Actor::getBaseLink() . '/d/' . $post->discussionId . '#' . $post->id;
    }
    
    public function __construct(Posted $event) {
        $this->event = $event;
    }

    public function handle() {
        $event = $this->event;
        $user = $event->actor;
        $post = $event->post;
        $contentSkim = '';
        if($post->content) {
            $contentSkim = substr($post->content, 0, 500);
        }
        $actorLink = Actor::getActorLink($user->username);
        $content = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => self::getPostLink($post) . '-created',
            'type' => 'Create',
            'to' => [$actorLink . '/followers'],
            'cc' => ['https://www.w3.org/ns/activitystreams#Public'],
            'actor' => $actorLink,
            'object' => [
                'id' => self::getPostLink($post),
                'url' => self::getPostLink($post),
                'type' => 'Note',
                'published' => gmdate('Y-m-d\TH:i:s\Z'),
                'attributedTo' => $actorLink,
                'content' => $contentSkim,
                'to' => [$actorLink . '/followers'],
                'cc' => ['https://www.w3.org/ns/activitystreams#Public']
            ]
        ];

        $send = new Send();        
        if($user) {
            foreach(Followship::where('id', $user->id)->get() as $ship) {
                $send->post($user->username, json_encode($content), $ship->inbox);
            }
        }

    }
}

class Post  {
    public function handle(Posted $event) {
        QueuedPost::dispatch($event)
    }
}
