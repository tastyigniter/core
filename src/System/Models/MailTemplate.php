<?php

namespace Igniter\System\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Mail\MailParser;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailManager;
use Illuminate\Support\Facades\View;

/**
 * MailTemplate Model Class
 *
 * @property int $template_id
 * @property int|null $layout_id
 * @property string|null $code
 * @property string $subject
 * @property string $body
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $label
 * @property bool|null $is_custom
 * @property string|null $plain_body
 * @property-read mixed $title
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate applyFilters(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate applySorts(array $sorts = [])
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate dropdown(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate like(string $column, string $value, string $side = 'both', string $boolean = 'and')
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate listFrontEnd(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate lists(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate newModelQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate newQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate orLike(string $column, string $value, string $side = 'both')
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate orSearch(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate query()
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate search(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate whereBody($value)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate whereCode($value)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate whereCreatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate whereIsCustom($value)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate whereLabel($value)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate whereLayoutId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate wherePlainBody($value)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate whereSubject($value)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate whereTemplateId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|MailTemplate whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class MailTemplate extends Model
{
    /**
     * @var string The database table name
     */
    protected $table = 'mail_templates';

    protected $primaryKey = 'template_id';

    protected $guarded = [];

    protected $casts = [
        'layout_id' => 'integer',
    ];

    public $relation = [
        'belongsTo' => [
            'layout' => [\Igniter\System\Models\MailLayout::class, 'foreignKey' => 'layout_id'],
        ],
    ];

    protected $appends = ['title'];

    public $timestamps = true;

    public static function getVariableOptions()
    {
        return resolve(MailManager::class)->listRegisteredVariables();
    }

    protected function afterFetch()
    {
        if (!$this->is_custom) {
            $this->fillFromView();
        }
    }

    //
    // Accessors & Mutators
    //

    public function getTitleAttribute($value)
    {
        $langLabel = !empty($this->attributes['label']) ? $this->attributes['label'] : '';

        return is_lang_key($langLabel) ? lang($langLabel) : $langLabel;
    }

    //
    // Helpers
    //

    public function fillFromContent($content)
    {
        $this->fillFromSections(MailParser::parse($content));
    }

    public function fillFromView()
    {
        $this->fillFromSections(self::getTemplateSections($this->code));
    }

    protected function fillFromSections(array $sections)
    {
        $this->subject = array_get($sections, 'settings.subject', 'No subject');
        $this->body = array_get($sections, 'html');
        $this->plain_body = array_get($sections, 'text');

        $layoutCode = array_get($sections, 'settings.layout', 'default');
        $this->layout_id = MailLayout::getIdFromCode($layoutCode);
    }

    /**
     * Synchronise all templates to the database.
     * @return void
     */
    public static function syncAll()
    {
        MailLayout::createLayouts();
        MailPartial::createPartials();

        $templates = (array)resolve(MailManager::class)->listRegisteredTemplates();
        $dbTemplates = self::lists('is_custom', 'code')->all();
        $newTemplates = array_diff_key($templates, (array)$dbTemplates);

        // Clean up non-customized templates
        foreach ($dbTemplates as $code => $is_custom) {
            if ($is_custom) {
                continue;
            }

            if (!array_key_exists($code, $templates)) {
                self::whereCode($code)->delete();
            }
        }

        // Create new templates
        foreach ($newTemplates as $name => $label) {
            $sections = self::getTemplateSections($name);
            $layoutCode = array_get($sections, 'settings.layout', 'default');

            $templateModel = self::make();
            $templateModel->code = $name;
            $templateModel->label = $label;
            $templateModel->is_custom = 0;
            $templateModel->layout_id = MailLayout::getIdFromCode($layoutCode);
            $templateModel->save();
        }
    }

    public static function findOrMakeTemplate($code)
    {
        if (!$template = self::whereCode($code)->first()) {
            $template = new self;
            $template->code = $code;
            $template->fillFromView();
        }

        return $template;
    }

    public static function listAllTemplates()
    {
        $registeredTemplates = (array)resolve(MailManager::class)->listRegisteredTemplates();
        $dbTemplates = (array)self::lists('code', 'code');
        $templates = $registeredTemplates + $dbTemplates;
        ksort($templates);

        return $templates;
    }

    protected static function getTemplateSections($code)
    {
        return MailParser::parse(File::get(View::make($code)->getPath()));
    }
}
