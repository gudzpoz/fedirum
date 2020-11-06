<?php

namespace Fedirum\Fedirum;

use Flarum\Database\AbstractModel;

class Followship extends AbstractModel {
    protected $table = 'fedirum';
    public $incrementing = false;
    public $follower = '';
    public $inbox = '';
    public $id = 0;

    protected $attributes = [
        'follower' => '',
        'inbox' => ''
    ];
}
