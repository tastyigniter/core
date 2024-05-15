<?php

namespace Igniter\System\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\HasPermalink;
use Igniter\System\Models\Concerns\Switchable;

/**
 * Page Class
 *
 * @internal
 */
class Page extends Model
{
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
            'language' => \Igniter\System\Models\Language::class,
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
