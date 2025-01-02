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
 * @method static \Igniter\Flame\Database\Builder<static>|Translation like(string $column, string $value, string $side = 'both', string $boolean = 'and')
 * @method static \Igniter\Flame\Database\Builder<static>|Translation listFrontEnd(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Translation lists(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation newModelQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Translation newQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Translation orLike(string $column, string $value, string $side = 'both')
 * @method static \Igniter\Flame\Database\Builder<static>|Translation orSearch(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|Translation pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static \Igniter\Flame\Database\Builder<static>|Translation query()
 * @method static \Igniter\Flame\Database\Builder<static>|Translation search(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|Translation whereCreatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation whereGroup($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation whereItem($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation whereLocale($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation whereLocked($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation whereNamespace($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation whereText($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation whereTranslationId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation whereUnstable($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Translation whereUpdatedAt($value)
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
