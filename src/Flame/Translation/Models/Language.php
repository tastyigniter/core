<?php

declare(strict_types=1);

namespace Igniter\Flame\Translation\Models;

use Igniter\Flame\Database\Builder;
use Igniter\Flame\Database\Model;

/**
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
 * @method static Builder<static>|Language applyFilters(array $options = [])
 * @method static Builder<static>|Language applySorts(array $sorts = [])
 * @method static Builder<static>|Language dropdown(string $column, string $key = null)
 * @method static Builder<static>|Language like(string $column, string $value, string $side = 'both', string $boolean = 'and')
 * @method static Builder<static>|Language listFrontEnd(array $options = [])
 * @method static Builder<static>|Language lists(string $column, string $key = null)
 * @method static Builder<static>|Language newModelQuery()
 * @method static Builder<static>|Language newQuery()
 * @method static Builder<static>|Language orLike(string $column, string $value, string $side = 'both')
 * @method static Builder<static>|Language orSearch(string $term, string $columns = [], string $mode = 'all')
 * @method static array pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static Builder<static>|Language query()
 * @method static Builder<static>|Language search(string $term, string $columns = [], string $mode = 'all')
 * @method static Builder<static>|Language whereCanDelete($value)
 * @method static Builder<static>|Language whereCode($value)
 * @method static Builder<static>|Language whereCreatedAt($value)
 * @method static Builder<static>|Language whereIdiom($value)
 * @method static Builder<static>|Language whereImage($value)
 * @method static Builder<static>|Language whereIsDefault($value)
 * @method static Builder<static>|Language whereLanguageId($value)
 * @method static Builder<static>|Language whereName($value)
 * @method static Builder<static>|Language whereOriginalId($value)
 * @method static Builder<static>|Language whereStatus($value)
 * @method static Builder<static>|Language whereUpdatedAt($value)
 * @method static Builder<static>|Language whereVersion($value)
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
