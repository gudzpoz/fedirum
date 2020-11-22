<?php

namespace Fedirum\Fedirum;

use Flarum\Post\Event\Posted;
use Flarum\Queue\AbstractJob;
use Parsedown;

class QueuedPost extends AbstractJob
{
    protected $event;
    public static function getRelativePostLink($discussion, $number) {
        return Actor::getBaseLink() . '/d/' . $discussion . '/' . $number;
    }
    public static function getPostLink($post) {
        return Actor::getBaseLink() . '/d/' . $post->discussion_id . '/' . $post->number;
    }
    public static function parsePostPath($path) {
        if(preg_match('/\\/d\\/(\\d+)(-[^\\/]+)?\\/?(\\d+)?\\/?/', $path, $match)) {
            if(array_key_exists(3, $match)) {
                return [(int)$match[1], (int)$match[3]];
            } else {
                return [(int)$match[1], 1];
            }
        } else {
            return null;
        }
    }
    public static function getPostObject($post) {
        $contentSkim = '';
        if($post->content) {
            $Parsedown = new Parsedown();
            $contentSkim = $Parsedown->text($post->content);
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
        $parent = null;
        if($post->number != 1) {
            $parent = self::getRelativePostLink($post->discussion_id, $post->number - 1);
        }
        $child = null;
        if($post->discussion->last_post_number > $post->number) {
            $child = [
                'type' => 'Collection',
                'totalItems' => 1,
                'items' => [self::getRelativePostLink($post->discussion_id, $post->number + 1)]
            ];
        }
        return [
            'id' => self::getPostLink($post),
            'url' => self::getPostLink($post),
            'type' => 'Note',
            'attachment' => $attachment,
            'published' => $post->created_at->toIso8601String(),
            'attributedTo' => $actorLink,
            'inReplyTo' => $parent,
            'replies' => $child,
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
