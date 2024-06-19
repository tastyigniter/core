<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Mail\Markdown;
use Illuminate\Support\HtmlString;

class PackageInfo
{
    public const CORE = 'tastyigniter/core';

    protected ?array $iconCache = null;

    public function __construct(
        public string $code,
        public string $package,
        public string $type,
        public string $name,
        public string $version,
        public string $author = '',
        public string $description = '',
        public array|string $icon = [],
        public string $installedVersion = '',
        public string $publishedAt = '',
        public array $tags = [],
        public string $hash = '',
        public string $updatedAt = '',
        public string $homepage = '',
    ) {}

    public static function fromArray(array $array): static
    {
        return new static(
            $array['code'],
            $array['package'],
            $array['type'],
            $array['name'],
            $array['version'],
            $array['author'],
            $array['description'] ?? '',
            $array['icon'] ?? [],
            $array['installedVersion'] ?? '',
            $array['published_at'] ?? $array['publishedAt'] ?? '',
            $array['tags'] ?? [],
            $array['hash'] ?? '',
            $array['updated_at'] ?? $array['updatedAt'] ?? '',
            $array['homepage'] ?? '',
        );
    }

    public function isCore(): bool
    {
        return $this->type === 'core';
    }

    public function icon(string $key, mixed $default): string
    {
        if (is_null($this->iconCache)) {
            $this->iconCache = generate_extension_icon($this->icon);
        }

        return array_get($this->iconCache, $key, $default);
    }

    public function changeLog(): HtmlString|string
    {
        if (!$tag = collect(array_get($this->tags, 'data', []))->first()) {
            return '';
        }

        return Markdown::parse($tag['description']);
    }

    public function publishedAt(): string
    {
        return make_carbon($this->publishedAt)->isoFormat(lang('igniter::system.moment.date_format'));
    }
}
