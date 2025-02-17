<?php

declare(strict_types=1);

namespace Igniter\System\Models;

use Igniter\Flame\Database\Builder;
use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\HasPermalink;
use Igniter\System\Models\Concerns\Switchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Page Class
 *
 * @internal
 * @property int $page_id
 * @property int $language_id
 * @property string $title
 * @property string $content
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int $status
 * @property string|null $permalink_slug
 * @property string|null $layout
 * @property array|null $metadata
 * @property int|null $priority
 * @method static Builder<static>|Page applyFilters(array $options = [])
 * @method static Builder<static>|Page applySorts(array $sorts = [])
 * @method static Builder<static>|Page applySwitchable(bool $switch = true)
 * @method static Collection dropdown(string $column, string $key = null)
 * @method static Builder<static>|Page findSimilarSlugs($attribute, array $config, $slug)
 * @method static Builder<static>|Page isEnabled()
 * @method static Builder<static>|Page listFrontEnd(array $options = [])
 * @method static array pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static Builder<static>|Page query()
 * @method static Builder<static>|Page whereSlug(string $slug)
 * @method static Builder<static>|Page whereIsEnabled()
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Page extends Model
{
    use HasFactory;
    use HasPermalink;
    use Switchable;

    /**
     * @var string The database table name
     */
    protected $table = 'pages';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'page_id';

    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'language_id' => 'integer',
        'metadata' => 'json',
    ];

    public $relation = [
        'belongsTo' => [
            'language' => Language::class,
        ],
    ];

    protected $permalinkable = [
        'permalink_slug' => [
            'source' => 'title',
        ],
    ];

    public static function getDropdownOptions()
    {
        return static::whereIsEnabled()->dropdown('title', 'permalink_slug');
    }
}
