<?php

declare(strict_types=1);

namespace Igniter\System\Models;

use Exception;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Mail\MailParser;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailManager;
use Illuminate\Support\Facades\View;

/**
 * MailPartial Model Class
 *
 * @property int $partial_id
 * @property string|null $name
 * @property string|null $code
 * @property string|null $html
 * @property string|null $text
 * @property bool $is_custom
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Igniter\Flame\Database\Builder<static>|MailPartial applyFilters(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|MailPartial applySorts(array $sorts = [])
 * @method static \Igniter\Flame\Database\Builder<static>|MailPartial dropdown(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|MailPartial lists(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|MailPartial listFrontEnd(array $options = [])
 * @method static array pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static \Igniter\Flame\Database\Builder<static>|MailPartial query()
 * @method static \Igniter\Flame\Database\Builder<static>|MailPartial whereCode($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class MailPartial extends Model
{
    protected static $codeCache;

    /**
     * @var string The database table name
     */
    protected $table = 'mail_partials';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'partial_id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    //
    // Events
    //

    protected function afterFetch()
    {
        if (!$this->is_custom) {
            $this->fillFromCode();
        }
    }

    //
    // Helpers
    //

    public function fillFromCode($code = null): void
    {
        if (is_null($code)) {
            $code = $this->code;
        }

        if (is_null($code)) {
            return;
        }

        $definitions = resolve(MailManager::class)->listRegisteredPartials();
        if (!$definition = array_get($definitions, $code)) {
            throw new SystemException('Unable to find a registered partial with code: '.$code);
        }

        $this->fillFromView($definition);
    }

    public function fillFromView($path): void
    {
        $sections = self::getTemplateSections($path);
        $this->name = array_get($sections, 'settings.name', '???');
        $this->html = array_get($sections, 'html');
        $this->text = array_get($sections, 'text');
    }

    public static function findOrMakePartial($code)
    {
        try {
            if (!$template = self::whereCode($code)->first()) {
                $template = new self;
                $template->code = $code;
                $template->fillFromCode();
            }

            return $template;
        } catch (Exception $ex) {
            return null;
        }
    }

    /**
     * Loops over each mail layout and ensures the system has a layout,
     * if the layout does not exist, it will create one.
     */
    public static function createPartials(): void
    {
        $dbPartials = self::lists('code', 'code')->all();
        $definitions = resolve(MailManager::class)->listRegisteredPartials();
        foreach ($definitions as $code => $path) {
            if (array_key_exists($code, (array)$dbPartials)) {
                continue;
            }

            $sections = self::getTemplateSections($path);

            $partial = new static;
            $partial->code = $code;
            $partial->is_custom = false;
            $partial->name = array_get($sections, 'settings.name', '???');
            $partial->save();
        }
    }

    protected static function getTemplateSections($code): array
    {
        return MailParser::parse(File::get(View::make($code)->getPath()));
    }
}
