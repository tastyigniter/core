<?php

namespace Igniter\System\Models;

/**
 *
 *
 * @property int $translation_id
 * @property string $locale
 * @property string $namespace
 * @property string $group
 * @property string $item
 * @property string $text
 * @property bool $unstable
 * @property bool $locked
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $code
 * @method static \Igniter\Flame\Database\Builder<static>|Translation applyFilters(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Translation applySorts(array $sorts = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Translation dropdown(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation listFrontEnd(array $options = [])
 * @method static array pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static \Igniter\Flame\Database\Builder<static>|Translation query()
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Translation extends \Igniter\Flame\Translation\Models\Translation
{
    /**
     * Update and lock translation.
     * When loading translations into the database, locked translations will not be overwritten .
     *
     * @return bool
     * @throws \Exception
     */
    public function updateAndLock($text)
    {
        $this->text = $text;

        return $this->lockState()->save();
    }
}
