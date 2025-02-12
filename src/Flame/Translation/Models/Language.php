<?php

namespace Igniter\Flame\Translation\Models;

use Igniter\Flame\Database\Model;

/**
 *
 *
 * @property int $language_id
 * @property string $code
 * @property string $name
 * @property string|null $image
 * @property string $idiom
 * @property int $status
 * @property int $can_delete
 * @property int|null $original_id
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $version
 * @property int $is_default
 * @method static \Igniter\Flame\Database\Builder<static>|Language applyFilters(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Language applySorts(array $sorts = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Language dropdown(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Language like(string $column, string $value, string $side = 'both', string $boolean = 'and')
 * @method static \Igniter\Flame\Database\Builder<static>|Language listFrontEnd(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Language lists(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Language newModelQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Language newQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Language orLike(string $column, string $value, string $side = 'both')
 * @method static \Igniter\Flame\Database\Builder<static>|Language orSearch(string $term, string $columns = [], string $mode = 'all')
 * @method static array pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static \Igniter\Flame\Database\Builder<static>|Language query()
 * @method static \Igniter\Flame\Database\Builder<static>|Language search(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereCanDelete($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereCode($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereCreatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereIdiom($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereImage($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereIsDefault($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereLanguageId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereName($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereOriginalId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereStatus($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereUpdatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Language whereVersion($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Language extends Model
{
    /**
     *  Table name in the database.
     * @var string
     */
    protected $table = 'languages';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'language_id';
}
