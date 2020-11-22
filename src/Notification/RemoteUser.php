<?php

namespace Fedirum\Fedirum\Notification;

class RemoteUser {
    public $name;
    public $url;

    public function __construct($name, $url) {
        $this->name = $name;
        $this->url = $url;
    }
}
