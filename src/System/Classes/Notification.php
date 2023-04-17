<?php

namespace Igniter\System\Classes;

use Igniter\Broadcast\Models\Settings as BroadcastSettings;
use Igniter\Flame\Database\Model;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class Notification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    protected ?Model $subject = null;

    protected string $title = '';

    protected string $message = '';

    protected string|null $url = null;

    protected string|null $icon = null;

    protected string|null $iconColor = null;

    public static function make(array $parameters = []): static
    {
        return app(static::class, $parameters);
    }

    public function broadcast(array $users = []): static
    {
        foreach ($users ?: $this->getRecipients() as $user) {
            $user->notify($this);
        }

        return $this;
    }

    public function via(object $notifiable)
    {
        $channels = ['database'];

        if (BroadcastSettings::isConfigured()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Returns an array of notification data
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->getTitle(),
            'icon' => $this->getIcon(),
            'iconColor' => $this->getIconColor(),
            'url' => $this->getUrl(),
            'message' => $this->getMessage(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function getRecipients(): array
    {
        return [];
    }

    public function subject(Model $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function message(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function iconColor(string $iconColor): static
    {
        $this->iconColor = $iconColor;

        return $this;
    }

    public function getIconColor(): ?string
    {
        return $this->iconColor;
    }
}
