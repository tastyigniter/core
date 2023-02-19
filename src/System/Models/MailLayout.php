<?php

namespace Igniter\System\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Mail\MailParser;
use Igniter\System\Classes\MailManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/**
 * MailLayout Model Class
 */
class MailLayout extends Model
{
    use HasFactory;

    protected static $codeCache;

    /**
     * @var string The database table name
     */
    protected $table = 'mail_layouts';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'layout_id';

    protected $guarded = [];

    /**
     * @var array The model table column to convert to dates on insert/update
     */
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

    public static function getDropdownOptions()
    {
        return self::dropdown('name');
    }

    protected function beforeDelete()
    {
        if ($this->is_locked) {
            throw new ApplicationException('You cannot delete a locked template');
        }
    }

    //
    // Scopes
    //

    /**
     * Scope a query to only include enabled mail template
     * @return $this
     */
    public function scopeIsEnabled($query)
    {
        return $query->where('status', 1);
    }

    //
    // Helpers
    //

    public static function listCodes()
    {
        if (self::$codeCache !== null) {
            return self::$codeCache;
        }

        return self::$codeCache = self::lists('layout_id', 'code');
    }

    public static function getIdFromCode($code)
    {
        return array_get(self::listCodes(), $code);
    }

    public function fillFromCode($code = null)
    {
        if (is_null($code))
            $code = $this->code;

        $definitions = resolve(MailManager::class)->listRegisteredLayouts();
        if (!$definition = array_get($definitions, $code))
            throw new ApplicationException('Unable to find a registered layout with code: '.$code);

        $this->fillFromView($definition);
    }

    public function fillFromView($path)
    {
        $sections = self::getTemplateSections($path);

        $this->layout_css = '';
        $this->name = array_get($sections, 'settings.name', '???');
        $this->layout = array_get($sections, 'html');
        $this->plain_layout = array_get($sections, 'text');
    }

    protected static function getTemplateSections($code)
    {
        return MailParser::parse(File::get(View::make($code)->getPath()));
    }

    /**
     * Loops over each mail layout and ensures the system has a layout,
     * if the layout does not exist, it will create one.
     * @return void
     */
    public static function createLayouts()
    {
        $dbLayouts = self::lists('code', 'code')->all();

        $definitions = resolve(MailManager::class)->listRegisteredLayouts();
        foreach ($definitions as $code => $path) {
            if (array_key_exists($code, $dbLayouts))
                continue;

            $layout = new static;
            $layout->code = $code;
            $layout->is_locked = true;
            $layout->fillFromView($path);
            $layout->save();
        }
    }
}
