<?php

namespace Fedirum\Fedirum;

use Flarum\Post\Event\Posted;
use Flarum\Queue\AbstractJob;
use Illuminate\Contracts\Queue\Factory;

class QueuedPost extends AbstractJob
{
    protected $event;
    public static function getPostLink($post) {
        return Actor::getBaseLink() . '/d/' . $post->discussion_id . '/' . $post->number;
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
        $attachment = [];
        preg_match_all('/\\!\\[([^\\[\\]\\n]*)\\]\\(([^()]+)\\)/', $post->content, $matches, PREG_SET_ORDER);
        foreach($matches as $match) {
            $attachment[] = [
                'type' => 'Image',
                'content' => $match[1],
                'url' => $match[2]
            ];
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
                'attachment' => $attachment,
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

class Post {
    protected $queue;
    public function __construct(Factory $factory) {
        $this->queue = $factory->connection('fedirum.posting');
    }
    
    public function handle(Posted $event) {
        $this->queue->push(new QueuedPost($event));
    }
}
