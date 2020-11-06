<?php

namespace Fedirum\Fedirum;

use Flarum\Database\AbstractModel;

class Followship extends AbstractModel {
    protected $table = 'fedirum';
    public $incrementing = false;

    protected $attributes = [
        'follower' => '',
        'inbox' => ''
    ];
}
