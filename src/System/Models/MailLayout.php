<?php

declare(strict_types=1);

namespace Igniter\System\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Mail\MailParser;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailManager;
use Igniter\System\Models\Concerns\Switchable;
use Illuminate\Support\Facades\View;

/**
 * MailLayout Model Class
 *
 * @property int $layout_id
 * @property string $name
 * @property int $language_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property bool $status
 * @property string $code
 * @property string|null $layout
 * @property string|null $plain_layout
 * @property string|null $layout_css
 * @property bool $is_locked
 * @method static \Igniter\Flame\Database\Builder<static>|MailLayout applyFilters(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|MailLayout applySorts(array $sorts = [])
 * @method static \Igniter\Flame\Database\Builder<static>|MailLayout applySwitchable(bool $switch = true)
 * @method static \Igniter\Flame\Database\Builder<static>|MailLayout dropdown(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|MailLayout isEnabled()
 * @method static \Igniter\Flame\Database\Builder<static>|MailLayout lists(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|MailLayout listFrontEnd(array $options = [])
 * @method static array pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class MailLayout extends Model
{
    use HasFactory;
    use Switchable;

    public static $codeCache;

    /**
     * @var string The database table name
     */
    protected $table = 'mail_layouts';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'layout_id';

    public $timestamps = true;

    protected $casts = [
        'language_id' => 'integer',
        'status' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public $relation = [
        'hasMany' => [
            'templates' => [\Igniter\System\Models\MailTemplate::class, 'foreignKey' => 'layout_id'],
        ],
        'belongsTo' => [
            'language' => \Igniter\System\Models\Language::class,
        ],
    ];

    protected $attributes = [
        'status' => true,
    ];

    public static function getDropdownOptions()
    {
        return self::dropdown('name');
    }

    protected function afterFetch()
    {
        if (!$this->is_locked) {
            $this->fillFromCode();
        }
    }

    //
    // Helpers
    //

    public static function listCodes()
    {
        if (self::$codeCache !== null) {
            return self::$codeCache;
        }

        return self::$codeCache = self::lists('code', 'layout_id');
    }

    public static function getIdFromCode($code)
    {
        return array_get(self::listCodes()->flip(), $code);
    }

    public function fillFromCode($code = null): void
    {
        if (is_null($code)) {
            $code = $this->code;
        }

        if (is_null($code)) {
            return;
        }

        $definitions = resolve(MailManager::class)->listRegisteredLayouts();
        if (!$definition = array_get($definitions, $code)) {
            throw new SystemException('Unable to find a registered layout with code: '.$code);
        }

        $this->fillFromView($definition);
    }

    public function fillFromView($path): void
    {
        $sections = self::getTemplateSections($path);

        $this->layout_css = '';
        $this->name = array_get($sections, 'settings.name', '???');
        $this->layout = array_get($sections, 'html');
        $this->plain_layout = array_get($sections, 'text');
    }

    protected static function getTemplateSections($code): array
    {
        return MailParser::parse(File::get(View::make($code)->getPath()));
    }

    /**
     * Loops over each mail layout and ensures the system has a layout,
     * if the layout does not exist, it will create one.
     */
    public static function createLayouts(): void
    {
        $dbLayouts = self::lists('code', 'code')->all();

        $definitions = resolve(MailManager::class)->listRegisteredLayouts();
        foreach ($definitions as $code => $path) {
            if (array_key_exists($code, (array)$dbLayouts)) {
                continue;
            }

            $sections = self::getTemplateSections($path);

            $layout = new static;
            $layout->code = $code;
            $layout->is_locked = false;
            $layout->name = array_get($sections, 'settings.name', '???');
            $layout->language_id = Language::getDefault()->getKey();
            $layout->save();
        }
    }
}
