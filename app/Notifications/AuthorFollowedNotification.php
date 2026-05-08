<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AuthorFollowedNotification extends Notification
{
    use Queueable;

    public function __construct(public User $follower) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'author.followed',
            'title' => __('Новий підписник'),
            'message' => __(':name підписався на ваш профіль.', ['name' => $this->follower->name]),
            'url' => route('authors.show', ['user' => $this->follower->username ?: $this->follower->id]),
            'icon' => 'user-plus',
            'follower_id' => $this->follower->id,
            'follower_name' => $this->follower->name,
        ];
    }
}
