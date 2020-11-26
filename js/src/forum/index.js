import { extend } from 'flarum/extend';
import app from 'flarum/app';
import Post from 'flarum/models/Post';
import Model from 'flarum/Model';
import NotificationGrid from 'flarum/components/NotificationGrid';
import UserCard from 'flarum/components/UserCard';
import TagHero from 'flarum/tags/components/TagHero';
import PostLikedNotification from './components/PostLikedNotification';

app.initializers.add('fedirum-likes', () => {
  app.notificationComponents.postRemoteLiked = PostLikedNotification;

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('postRemoteLiked', {
      name: 'postRemoteLiked',
      icon: 'far fa-thumbs-up',
      label: app.translator.trans('flarum-likes.forum.settings.notify_post_liked_label')
    });
  });
});

extend(UserCard.prototype, 'infoItems', function(items) {
  var actor = '@' + this.attrs.user.username() + '@' + document.location.hostname;
  items.add('actorId', <div>{actor}</div>);
});

extend(TagHero.prototype, 'view', function(content) {
  content.children.push(<div>{ '@' + app.forum.attribute('tagActorPrefix') + this.attrs.model.slug() + '@' + document.location.hostname }</div>);
});
