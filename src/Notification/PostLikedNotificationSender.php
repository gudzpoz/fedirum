<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Fedirum\Fedirum\Notification;

use Flarum\Api\Serializer\PostSerializer;
use Flarum\Event\ConfigureNotificationTypes;
use Illuminate\Contracts\Events\Dispatcher;

class PostLikedNotificationSender
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureNotificationTypes::class, [$this, 'addNotificationType']);
    }
    public function addNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(PostLikedBlueprint::class, PostSerializer::class, ['alert']);
    }
}
