<?php

namespace Igniter\System\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\Switchable;
use Illuminate\Support\Facades\Lang;

/**
 * Language Model Class
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
    ];

    public $relation = [
        'hasMany' => [
            'translations' => [\Igniter\System\Models\Translation::class, 'foreignKey' => 'locale', 'otherKey' => 'code', 'delete' => true],
        ],
    ];

    public $timestamps = true;

    /**
     *  List of variables that cannot be mass assigned
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array Object cache of self, by code.
     */
    protected static $localesCache = [];

    /**
     * @var array A cache of supported locales.
     */
    protected static $supportedLocalesCache;

    /**
     * @var self Active language cache.
     */
    protected static $activeLanguage;

    public static function applySupportedLanguages()
    {
        setting()->set('supported_languages', self::getDropdownOptions()->keys()->toArray());
    }

    public static function getDropdownOptions()
    {
        return self::whereIsEnabled()->dropdown('name', 'code');
    }

    //
    // Helpers
    //

    public static function findByCode($code = null)
    {
        if (!$code) {
            return null;
        }

        if (isset(self::$localesCache[$code])) {
            return self::$localesCache[$code];
        }

        return self::$localesCache[$code] = self::whereCode($code)->first();
    }

    public function makeDefault()
    {
        if (!$this->status) {
            throw new ValidationException(['status' => sprintf(
                lang('igniter::admin.alert_error_set_default'), $this->name
            )]);
        }

        setting('default_language', $this->code);
        setting()->save();
    }

    public function defaultableKeyName(): string
    {
        return 'code';
    }

    public static function getActiveLocale()
    {
        if (self::$activeLanguage !== null) {
            return self::$activeLanguage;
        }

        $activeLanguage = self::applySwitchable()
            ->where('code', app()->getLocale())
            ->first();

        return self::$activeLanguage = $activeLanguage;
    }

    public static function listSupported()
    {
        if (self::$supportedLocalesCache) {
            return self::$supportedLocalesCache;
        }

        return self::$supportedLocalesCache = self::whereIsEnabled()->pluck('name', 'code')->all();
    }

    public static function supportsLocale()
    {
        return count(self::listSupported()) > 1;
    }

    //
    // Translations
    //

    public function listAllFiles()
    {
        traceLog('Method Language::listAllFiles() has been deprecated. Use Translator loader instead.');
    }

    public function getLines($locale, $group, $namespace = null)
    {
        $lines = app('translation.loader')->load($locale, $group, $namespace);

        ksort($lines);

        return array_dot($lines);
    }

    public function getTranslations($group, $namespace = null)
    {
        return $this->getLines($this->code, $group, $namespace);
    }

    public function addTranslations($translations)
    {
        $languageId = $this->getKey();
        if (!is_numeric($languageId)) {
            return false;
        }

        foreach ($translations as $key => $translation) {
            preg_match('/^(.+)::(?:(.+?))\.(.+)+$/', $key, $matches);

            if (!$matches || count($matches) !== 4) {
                continue;
            }

            [$code, $namespace, $group, $item] = $matches;

            $this->updateTranslation($group, $namespace, $item, $translation['translation']);
        }
    }

    public function updateTranslations($group, $namespace = null, array $lines = [])
    {
        return collect($lines)->map(function ($text, $key) use ($group, $namespace) {
            $this->updateTranslation($group, $namespace, $key, $text);

            return $text;
        })->filter()->toArray();
    }

    public function updateTranslation($group, $namespace, $key, $text)
    {
        $oldText = Lang::get("{$namespace}::{$group}.{$key}", [], $this->code);

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
}
