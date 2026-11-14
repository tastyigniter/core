<?php

declare(strict_types=1);

namespace Igniter\System\Models;

use Exception;
use Igniter\Flame\Database\Builder;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Mail\MailParser;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\MailBuilder;
use Igniter\System\Classes\MailManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Override;

/**
 * MailPartial Model Class
 *
 * @property int $partial_id
 * @property string|null $name
 * @property string|null $code
 * @property string|null $html
 * @property string|null $text
 * @property bool $is_custom
 * @property array|null $blocks
 * @property array|null $params
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|MailPartial applyFilters(array $options = [])
 * @method static Builder<static>|MailPartial applySorts(array $sorts = [])
 * @method static Builder<static>|MailPartial listFrontEnd(array $options = [])
 * @method static Builder<static>|MailPartial newModelQuery()
 * @method static Builder<static>|MailPartial newQuery()
 * @method static Builder<static>|MailPartial query()
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
        'blocks' => 'array',
        'params' => 'array',
    ];

    //
    // Events
    //

    #[Override]
    protected function beforeSave()
    {
        $this->compileBuilderContent();
    }

    #[Override]
    protected function afterFetch()
    {
        if (!$this->is_custom) {
            $this->fillFromCode();
        }
    }

    //
    // Helpers
    //

    public function usesBuilder(): bool
    {
        return !is_null($this->blocks);
    }

    /**
     * The registered view the partial is defined in, when it is a registered partial.
     */
    public function getRegisteredView(): ?string
    {
        return array_get(resolve(MailManager::class)->listRegisteredPartials(), (string)$this->code);
    }

    /**
     * Compiles the builder blocks into the partial html and text.
     */
    public function compileBuilderContent(): void
    {
        if (!$this->usesBuilder()) {
            return;
        }

        $builder = resolve(MailBuilder::class);
        $context = $builder->makeContext(CompileContext::MODE_PARTIAL, $this->code, $this->getParamDefinitions());
        $blocks = $builder->normalize($this->blocks, $context);

        $this->blocks = $blocks;
        $this->html = $builder->compileHtml($blocks, $context);
        $this->text = $builder->compileText($blocks, $context);
    }

    /**
     * Returns the declared partial parameters as a list of name, label and sample.
     */
    public function getParamDefinitions(): array
    {
        $params = [];
        foreach ((array)$this->params as $param) {
            $name = is_array($param) ? (string)($param['name'] ?? '') : '';
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) && !in_array($name, ['slot', 'body'], true)) {
                $params[$name] = [
                    'name' => $name,
                    'label' => (string)($param['label'] ?? $name),
                    'sample' => (string)($param['sample'] ?? ''),
                ];
            }
        }

        return array_values($params);
    }

    public function fillFromCode($code = null): void
    {
        $code ??= $this->code;

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
        } catch (Exception) {
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
        /** @var View $view */
        $view = ViewFacade::make($code);

        return MailParser::parse(File::get($view->getPath()));
    }
}
