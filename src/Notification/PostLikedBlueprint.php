<?php

namespace Fedirum\Fedirum\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Post\Post;

class PostLikedBlueprint implements BlueprintInterface
{
    public $post;
    public $user;

    public function __construct(Post $post, RemoteUser $user) {
        $this->post = $post;
        $this->user = $user;
    }

    public function getSubject() {
        return $this->post;
    }

    public function getSender() {
        return null;
    }

    public function getData() {
        return [
            'username' => $this->user->name,
            'url' => $this->user->url
        ];
    }

    public static function getType() {
        return 'postRemoteLiked';
    }

    public static function getSubjectModel() {
        return Post::class;
    }
}
