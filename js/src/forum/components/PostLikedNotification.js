import Notification from 'flarum/components/Notification';
import { truncate } from 'flarum/utils/string';

export default class PostLikedNotification extends Notification {
  icon() {
    return 'far fa-thumbs-up';
  }

  href() {
    return app.route.post(this.attrs.notification.subject());
  }

  content() {
    const notification = this.attrs.notification;

    return 'A remote user liked your post.';
  }

  excerpt() {
    return 'From: ' + this.attrs.notification.content().url;
  }
}
