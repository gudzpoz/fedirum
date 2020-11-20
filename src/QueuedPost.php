<?php

namespace Fedirum\Fedirum;

use Flarum\Post\Event\Posted;
use Flarum\Queue\AbstractJob;

class QueuedPost extends AbstractJob
{
    protected $event;
    public static function getPostLink($post) {
        return Actor::getBaseLink() . '/d/' . $post->discussion_id . '/' . $post->number;
    }
    public static function getPostObject($post) {
        $contentSkim = '';
        if($post->content) {
            $contentSkim = substr($post->content, 0, 500);
        }
        $actorLink = Actor::getActorLink($post->user->username);
        $attachment = [];
        preg_match_all('/\\!\\[([^\\[\\]\\n]*)\\]\\(([^()]+)\\)/', $post->content, $matches, PREG_SET_ORDER);
        foreach($matches as $match) {
            $attachment[] = [
                'type' => 'Image',
                'content' => $match[1],
                'url' => $match[2]
            ];
        }
        return [
            'id' => self::getPostLink($post),
            'url' => self::getPostLink($post),
            'type' => 'Note',
            'attachment' => $attachment,
            'published' => gmdate('Y-m-d\TH:i:s\Z'),
            'attributedTo' => $actorLink,
            'content' => $contentSkim,
            'to' => [$actorLink . '/followers'],
            'cc' => ['https://www.w3.org/ns/activitystreams#Public']
        ];
    }
    
    public function __construct(Posted $event) {
        $this->event = $event;
    }

    public function handle() {
        $event = $this->event;
        $user = $event->actor;
        $post = $event->post;
        $actorLink = Actor::getActorLink($user->username);
        $content = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => self::getPostLink($post) . '-created',
            'type' => 'Create',
            'to' => [$actorLink . '/followers'],
            'cc' => ['https://www.w3.org/ns/activitystreams#Public'],
            'actor' => $actorLink,
            'object' => self::getPostObject($post),
        ];

        $send = new Send();        
        if($user) {
            foreach(Followship::where('id', $user->id)->get() as $ship) {
                $send->post($user->username, json_encode($content), $ship->inbox);
            }
        }
    }
}
