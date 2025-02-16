<?php

declare(strict_types=1);

namespace Igniter\Flame\Translation\Models;

use Igniter\Flame\Database\Model;
use Illuminate\Support\Facades\Cache;

/**
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
 * @method static array pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
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
class Translation extends Model
{
    protected static $cacheKey = 'igniter.translation';

    public $timestamps = true;

    /**
     *  Table name in the database.
     * @var string
     */
    protected $table = 'language_translations';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'translation_id';

    /**
     *  List of variables that can be mass assigned
     * @var array
     */
    protected $fillable = ['locale', 'namespace', 'group', 'item', 'text', 'unstable'];

    protected $casts = [
        'unstable' => 'boolean',
        'locked' => 'boolean',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::saved(function(Translation $model) {
            $model->flushCache();
        });

        static::deleted(function(Translation $model) {
            $model->flushCache();
        });
    }

    public static function getCacheKey($locale, $group, $namespace): string
    {
        return static::$cacheKey.".{$locale}.{$namespace}.{$group}";
    }

    /**
     *  Returns the full translation code for an entry: namespace.group.item
     */
    public function getCodeAttribute(): string
    {
        return $this->namespace === '*' ? "{$this->group}.{$this->item}" : "{$this->namespace}::{$this->group}.{$this->item}";
    }

    /**
     *  Flag this entry as Reviewed
     * @return \Igniter\Flame\Translation\Models\Translation
     */
    public function flagAsReviewed()
    {
        $this->unstable = false;

        return $this;
    }

    /**
     *  Flag this entry as pending review.
     */
    public function flagAsUnstable(): void
    {
        $this->unstable = true;

        $this->save();
    }

    /**
     *  Set the translation to the locked state
     * @return \Igniter\Flame\Translation\Models\Translation
     */
    public function lockState()
    {
        $this->locked = true;

        return $this;
    }

    /**
     *  Check if the translation is locked
     */
    public function isLocked(): bool
    {
        return (bool)$this->locked;
    }

    protected function flushCache()
    {
        Cache::forget(static::getCacheKey($this->locale, $this->group, $this->namespace));
    }

    public static function getFresh($locale, $group, $namespace = null)
    {
        return static::query()
            ->where('locale', $locale)
            ->where('group', $group)
            ->where('namespace', $namespace)
            ->get();
    }

    public static function getCached($locale, $group, $namespace = null)
    {
        $cacheKey = static::getCacheKey($locale, $group, $namespace);

        return Cache::rememberForever($cacheKey, function() use ($locale, $group, $namespace) {
            $result = static::getFresh($locale, $group, $namespace)->reduce(
                function($lines, Translation $model) {
                    array_set($lines, $model->item, $model->text);

                    return $lines;
                },
            );

            return $result ?: [];
        },
        );
    }
}
