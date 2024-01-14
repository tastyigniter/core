<?php

namespace Igniter\Main\Template;

use Igniter\Flame\Mail\Markdown;
use Igniter\Flame\Pagic\Model;
use Igniter\Flame\Support\Facades\File;

class Content extends Model
{
    /** The directory name associated with the model */
    public const DIR_NAME = '_content';

    public static function initCacheItem(array &$item)
    {
        $item['parsedMarkup'] = (new static($item))->parseMarkup();
    }

    public function getParsedMarkupAttribute(): ?string
    {
        if (array_key_exists('parsedMarkup', $this->attributes)) {
            return $this->attributes['parsedMarkup'];
        }

        return $this->attributes['parsedMarkup'] = $this->parseMarkup();
    }

    /**
     * Parses the content markup according to the file type.
     */
    public function parseMarkup(): ?string
    {
        $extension = strtolower(File::extension($this->fileName));

        return match ($extension) {
            'txt' => htmlspecialchars($this->markup),
            'md' => Markdown::parse($this->markup)->toHtml(),
            default => $this->markup,
        };
    }
}
