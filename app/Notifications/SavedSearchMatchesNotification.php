<?php

namespace App\Notifications;

use App\Models\SavedSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SavedSearchMatchesNotification extends Notification
{
    use Queueable;

    /**
     * @param array<int, array{title: string, slug: string, price: string|null}> $matches
     */
    public function __construct(public SavedSearch $search, public array $matches) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $msg = (new MailMessage)
            ->subject(__('Нові моделі за пошуком «:name»', ['name' => $this->search->name]))
            ->greeting(__('Привіт, :name!', ['name' => $notifiable->name]))
            ->line(__('Ми знайшли :count нових моделей за вашим збереженим пошуком.', ['count' => count($this->matches)]));

        foreach (array_slice($this->matches, 0, 5) as $m) {
            $msg->line('• '.$m['title'].($m['price'] ? ' — '.$m['price'] : ''));
        }

        $msg->action(__('Переглянути усі результати'), $this->search->url());

        return $msg;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('Нові моделі за «:name»', ['name' => $this->search->name]),
            'message' => __(':count нових моделей збігаються з вашим збереженим пошуком.', ['count' => count($this->matches)]),
            'url' => $this->search->url(),
            'icon' => 'search',
        ];
    }
}
