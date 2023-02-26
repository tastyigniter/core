<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Database\Model;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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

    public static function make(): static
    {
        return app(static::class);
    }

    public function sendToDatabase(array $recipients = []): static
    {
        foreach ($recipients ?: $this->getRecipients() as $user) {
            $user->notify($this->toDatabase());
        }

        return $this;
    }

    /**
     * Returns an array of notification data
     */
    public function toDatabase(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'title' => $this->getTitle(),
            'icon' => $this->getIcon(),
            'iconColor' => $this->getIconColor(),
            'url' => $this->getUrl(),
            'message' => $this->getMessage(),
        ];
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
