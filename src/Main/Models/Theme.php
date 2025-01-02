<?php

namespace Igniter\Main\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\Main\Classes\Theme as ThemeData;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Events\ThemeActivatedEvent;
use Igniter\Main\Template\Layout;
use Igniter\System\Classes\ComponentManager;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\Switchable;

/**
 * Theme Model Class
 *
 * @property int $theme_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string|null $version
 * @property array|null $data
 * @property bool $status
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $author
 * @property-read mixed $locked
 * @property-read mixed $screenshot
 * @method static \Igniter\Flame\Database\Builder<static>|Theme applyDefaultable(bool $default = true)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme applyFilters(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Theme applySorts(array $sorts = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Theme applySwitchable(bool $switch = true)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme dropdown(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme isEnabled()
 * @method static \Igniter\Flame\Database\Builder<static>|Theme like(string $column, string $value, string $side = 'both', string $boolean = 'and')
 * @method static \Igniter\Flame\Database\Builder<static>|Theme listFrontEnd(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Theme lists(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme newModelQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Theme newQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Theme orLike(string $column, string $value, string $side = 'both')
 * @method static \Igniter\Flame\Database\Builder<static>|Theme orSearch(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|Theme pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static \Igniter\Flame\Database\Builder<static>|Theme query()
 * @method static \Igniter\Flame\Database\Builder<static>|Theme search(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereCode($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereCreatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereData($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereDescription($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereIsDefault($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereIsDisabled()
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereIsEnabled()
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereName($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereNotDefault()
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereStatus($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereThemeId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereUpdatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Theme whereVersion($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Theme extends Model
{
    use Defaultable;
    use Purgeable;
    use Switchable;

    const ICON_MIMETYPES = [
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
    ];

    /**
     * @var array data cached array
     */
    protected static $instances = [];

    /**
     * @var string The database table code
     */
    protected $table = 'themes';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'theme_id';

    protected $fillable = ['theme_id', 'name', 'code', 'version', 'description', 'data', 'status'];

    protected $casts = [
        'data' => 'array',
        'status' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected $purgeable = ['template', 'settings', 'markup', 'codeSection'];

    public $timestamps = true;

    protected $fieldConfig;

    protected $fieldValues = [];

    public static function forTheme(ThemeData $theme)
    {
        $themeCode = $theme->getName();
        if ($instance = array_get(self::$instances, $themeCode)) {
            return $instance;
        }

        $instance = self::firstOrCreate(['code' => $themeCode]);

        return self::$instances[$themeCode] = $instance;
    }

    public static function onboardingIsComplete()
    {
        if (!$model = self::getDefault()) {
            return false;
        }

        return !is_null($model->data);
    }

    public function getLayoutOptions()
    {
        return Layout::getDropdownOptions($this->getTheme()->getName());
    }

    public static function getComponentOptions()
    {
        $components = [];
        $manager = resolve(ComponentManager::class);
        foreach ($manager->listComponentObjects() as $code => $componentData) {
            if ($componentData->component->isHidden()) {
                continue;
            }

            $components[$code] = [$componentData->name, lang($componentData->description ?? '')];
        }

        return $components;
    }

    //
    // Accessors & Mutators
    //

    public function getNameAttribute($value)
    {
        return optional($this->getTheme())->label ?? $value;
    }

    public function getDescriptionAttribute($value)
    {
        return optional($this->getTheme())->description ?? $value;
    }

    public function getVersionAttribute($value = null)
    {
        return $value ?? '0.1.0';
    }

    public function getAuthorAttribute($value)
    {
        return optional($this->getTheme())->author ?? $value;
    }

    public function getLockedAttribute()
    {
        return $this->getTheme()?->locked;
    }

    public function getScreenshotAttribute()
    {
        return $this->getTheme()?->getScreenshotData();
    }

    public function setAttribute($key, $value)
    {
        if (!$this->isFillable($key)) {
            $this->fieldValues[$key] = $value;
        } else {
            parent::setAttribute($key, $value);
        }
    }

    //
    // Events
    //

    protected function beforeSave()
    {
        if ($this->fieldValues) {
            $this->data = $this->fieldValues;
        }
    }

    //
    // Manager
    //

    public function getManager()
    {
        return resolve(ThemeManager::class);
    }

    public function getTheme()
    {
        return $this->getManager()->findTheme($this->code);
    }

    public function getFieldsConfig()
    {
        if (!is_null($this->fieldConfig)) {
            return $this->fieldConfig;
        }

        $fields = [];
        $formConfig = $this->getTheme()->getFormConfig();
        foreach ($formConfig as $item) {
            foreach (array_get($item, 'fields', []) as $name => $field) {
                if (!isset($field['tab'])) {
                    $field['tab'] = $item['title'];
                }

                $fields[$name] = $field;
            }
        }

        return $this->fieldConfig = $fields;
    }

    public function getFieldValues()
    {
        return $this->data ?: [];
    }

    public function getThemeData()
    {
        return $this->data;
    }

    //
    // Helpers
    //

    public static function syncAll()
    {
        $installedThemes = [];
        $manifest = resolve(PackageManifest::class);
        $themeManager = resolve(ThemeManager::class);
        foreach ($themeManager->paths() as $code => $path) {
            if (!($themeObj = $themeManager->findTheme($code))) {
                continue;
            }

            $installedThemes[] = $name = $themeObj->name ?? $code;

            // Only add themes whose meta code match their directory name
            if ($code != $name) {
                continue;
            }

            $theme = self::firstOrNew(['code' => $name]);
            $theme->name = $themeObj->label ?? title_case($code);
            $theme->code = $name;
            $theme->version = $manifest->getVersion($theme->code) ?? $theme->version;
            $theme->description = $themeObj->description ?? '';
            $theme->data = [];
            $theme->save();
        }

        // Disable themes not found in file system
        // This allows admin to remove an enabled theme from admin UI after deleting files
        self::whereNotIn('code', $installedThemes)->update(['status' => false]);
        self::whereIn('code', $installedThemes)->update(['status' => true]);
    }

    /**
     * Activate theme
     *
     * @param string $code
     *
     * @return bool|mixed
     */
    public static function activateTheme($code, $skipRequires = false)
    {
        if (empty($code) || !$theme = self::whereCode($code)->first()) {
            return false;
        }

        $extensionManager = resolve(ExtensionManager::class);

        foreach ($skipRequires ? [] : $theme->getTheme()->listRequires() as $extensionCode => $version) {
            if ($extensionManager->hasExtension($extensionCode)) {
                $extensionManager->installExtension($extensionCode);
            }
        }

        $theme->makeDefault();

        ThemeActivatedEvent::dispatch($theme);

        return $theme;
    }

    public static function generateUniqueCode($code, $suffix = null)
    {
        do {
            $uniqueCode = $code.($suffix ? '-'.$suffix : '');
            $suffix = strtolower(str_random(3));
        } while (self::themeCodeExists($uniqueCode)); // Already in the DB? Fail. Try again

        return $uniqueCode;
    }

    /**
     * Checks whether a code exists in the database or not
     *
     * @param string $uniqueCode
     * @return bool
     */
    protected static function themeCodeExists($uniqueCode)
    {
        return self::where('code', '=', $uniqueCode)->limit(1)->count() > 0;
    }
}
