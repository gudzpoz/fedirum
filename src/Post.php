<?php

namespace Fedirum\Fedirum;

use Flarum\Post\Event\Posted;

return [
    
];

class Post
{
    public static function getPostLink($post) {
        return Actor::getBaseLink() . '/d/' . $post->discussionId . '#' . $post->id;
    }
    
    public function __construct() {
    }

    public function handle(Posted $event) {
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
