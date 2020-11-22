import { extend } from 'flarum/extend';
import app from 'flarum/app';
import Post from 'flarum/models/Post';
import Model from 'flarum/Model';
import NotificationGrid from 'flarum/components/NotificationGrid';

import PostLikedNotification from './components/PostLikedNotification';

app.initializers.add('flarum-likes', () => {
  app.notificationComponents.postLiked = PostLikedNotification;

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('postRemoteLiked', {
      name: 'postRemoteLiked',
      icon: 'far fa-thumbs-up',
      label: app.translator.trans('flarum-likes.forum.settings.notify_post_liked_label')
    });
  });
});
