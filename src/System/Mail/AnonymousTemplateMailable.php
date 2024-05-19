<?php

namespace Igniter\System\Mail;

use Igniter\Flame\Database\Model;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class AnonymousTemplateMailable extends TemplateMailable
{
    use Queueable, SerializesModels;

    public static function create(string $templateCode): static
    {
        $instance = new static;

        $instance->templateCode = $templateCode;

        return $instance;
    }

    public function with($key, $value = null): static
    {
        if (is_array($key)) {
            $key = array_filter($key, function($v) {
                return !$v instanceof Model;
            });
        }

        return parent::with($key, $value);
    }

    public function applyCallback(mixed $callback): static
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
