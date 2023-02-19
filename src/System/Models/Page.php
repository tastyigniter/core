<?php

namespace Igniter\System\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\HasPermalink;

/**
 * Page Class
 */
class Page extends Model
{
    use HasPermalink;

    /**
     * @var string The database table name
     */
    protected $table = 'pages';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'page_id';

    /**
     * @var array The model table column to convert to dates on insert/update
     */
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'language_id' => 'integer',
        'metadata' => 'json',
        'status' => 'boolean',
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
        return static::isEnabled()->dropdown('title');
    }

    //
    // Scopes
    //

    /**
     * Scope a query to only include enabled page
     *
     * @param $query
     *
     * @return $this
     */
    public function scopeIsEnabled($query)
    {
        return $query->where('status', 1);
    }
}
