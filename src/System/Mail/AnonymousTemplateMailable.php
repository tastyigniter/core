<?php

declare(strict_types=1);

namespace Igniter\System\Mail;

use Igniter\Flame\Database\Model;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

final class AnonymousTemplateMailable extends TemplateMailable
{
    use Queueable;
    use SerializesModels;

    public static function create(string $templateCode): self
    {
        $instance = new self;

        $instance->templateCode = $templateCode;

        return $instance;
    }

    public function with($key, $value = null): self
    {
        if (is_array($key)) {
            $key = array_filter($key, fn($v): bool => !$v instanceof Model);
        }

        return parent::with($key, $value);
    }

    public function applyCallback(mixed $callback): self
    {
        if (is_callable($callback)) {
            $this->withSymfonyMessage($callback);
        } elseif (is_array($callback)) {
            $this->to(...$callback);
        } elseif (!is_null($callback)) {
            $this->to($callback);
        }

        return $this;
    }
}
