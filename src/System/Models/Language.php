<?php

namespace Igniter\System\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\System\Classes\LanguageManager;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\Switchable;
use Illuminate\Support\Facades\Lang;

/**
 * Language Model Class
 *
 * @property int $language_id
 * @property string $code
 * @property string $name
 * @property string|null $image
 * @property string $idiom
 * @property int $status
 * @property int $can_delete
 * @property int|null $original_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array|null $version
 * @property bool $is_default
 * @method static \Igniter\Flame\Database\Builder<static>|Language applyDefaultable(bool $default = true)
 * @method static \Igniter\Flame\Database\Builder<static>|Language applyFilters(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Language applySorts(array $sorts = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Language applySwitchable(bool $switch = true)
 * @method static \Igniter\Flame\Database\Builder<static>|Language dropdown(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Language isEnabled()
 * @method static \Igniter\Flame\Database\Builder<static>|Language like(string $column, string $value, string $side = 'both', string $boolean = 'and')
 * @method static \Igniter\Flame\Database\Builder<static>|Language listFrontEnd(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Language lists(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Language newModelQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Language newQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Language orLike(string $column, string $value, string $side = 'both')
 * @method static \Igniter\Flame\Database\Builder<static>|Language orSearch(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|Language pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static \Igniter\Flame\Database\Builder<static>|Language query()
 * @method static \Igniter\Flame\Database\Builder<static>|Language search(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereCanDelete($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereCode($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereCreatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereIdiom($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereImage($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereIsDefault($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereIsDisabled()
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereIsEnabled()
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereLanguageId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereName($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereNotDefault()
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereOriginalId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereStatus($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereUpdatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereVersion($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Language extends \Igniter\Flame\Translation\Models\Language
{
    use Defaultable;
    use HasFactory;
    use Purgeable;
    use Switchable;

    protected $purgeable = ['translations'];

    protected $casts = [
        'original_id' => 'integer',
        'version' => 'array',
    ];

    public $relation = [
        'hasMany' => [
            'translations' => [\Igniter\System\Models\Translation::class, 'foreignKey' => 'locale', 'otherKey' => 'code', 'delete' => true],
        ],
    ];

    public $timestamps = true;

    protected $attributes = [
        'can_delete' => 0,
    ];

    /** Object cache of self, by code. */
    public static array $localesCache = [];

    /** A cache of supported locales. */
    public static ?array $supportedLocalesCache = null;

    /** Active language cache. */
    public static ?self $activeLanguage = null;

    public static function applySupportedLanguages()
    {
        setting()->setPref('supported_languages', self::getDropdownOptions()->keys()->toArray());
    }

    public static function getDropdownOptions()
    {
        return self::whereIsEnabled()->dropdown('name', 'code');
    }

    //
    // Helpers
    //

    public static function findByCode(?string $code = null): ?static
    {
        if (!$code) {
            return null;
        }

        return self::$localesCache[$code] ?? (self::$localesCache[$code] = self::whereCode($code)->first());
    }

    public function defaultableKeyName(): string
    {
        return 'code';
    }

    public static function getActiveLocale(): ?self
    {
        if (self::$activeLanguage !== null) {
            return self::$activeLanguage;
        }

        /** @var static $activeLanguage */
        $activeLanguage = self::applySwitchable()
            ->where('code', app()->getLocale())
            ->first();

        return self::$activeLanguage = $activeLanguage;
    }

    public static function listSupported(): array
    {
        if (self::$supportedLocalesCache) {
            return self::$supportedLocalesCache;
        }

        return self::$supportedLocalesCache = self::whereIsEnabled()->pluck('name', 'code')->all();
    }

    public static function supportsLocale(): bool
    {
        return count(self::listSupported()) > 1;
    }

    //
    // Translations
    //

    public function getGroupOptions(?string $locale = null)
    {
        return collect(resolve(LanguageManager::class)->listLocalePackages($locale))
            ->mapWithKeys(function($localePackage) {
                return [$localePackage->code => $localePackage->name];
            });
    }

    public function getLines(string $locale, string $group, ?string $namespace = null): array
    {
        $lines = app('translation.loader')->load($locale, $group, $namespace);

        ksort($lines);

        return array_dot($lines);
    }

    public function getTranslations(string $group, ?string $namespace = null): array
    {
        return $this->getLines($this->code, $group, $namespace);
    }

    public function addTranslations(array $translations): bool
    {
        $languageId = $this->getKey();
        if (!is_numeric($languageId)) {
            return false;
        }

        foreach ($translations as $key => $translation) {
            preg_match('/^(.+)::(.+?)\.(.+)+$/', $key, $matches);

            if (!$matches || count($matches) !== 4) {
                continue;
            }

            [, $namespace, $group, $item] = $matches;

            $this->updateTranslation($group, $namespace, $item, (string)$translation['translation']);
        }

        return true;
    }

    public function updateTranslations(string $group, ?string $namespace = null, array $lines = []): array
    {
        return collect($lines)->map(function($text, $key) use ($group, $namespace) {
            $this->updateTranslation($group, $namespace, $key, $text);

            return $text;
        })->filter()->toArray();
    }

    public function updateTranslation(string $group, string $namespace, string $key, string $text)
    {
        $oldText = Lang::get("$namespace::$group.$key", [], $this->code);

        if (strcmp($text, $oldText) === 0) {
            return false;
        }

        $translation = $this->translations()->firstOrNew([
            'group' => $group,
            'namespace' => $namespace,
            'item' => $key,
        ]);

        $translation->updateAndLock($text);
    }

    public function updateVersions(array $meta)
    {
        $version = (array)($this->version ?? []);
        $version = array_set($version, $meta['code'], $meta['version']);
        $this->version = $version;

        return $this->save();
    }
}
