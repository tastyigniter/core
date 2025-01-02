<?php

namespace Igniter\System\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Support\Facades\Igniter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Request;

/**
 * RequestLog Model Class
 *
 * @property int $id
 * @property string|null $url
 * @property int|null $status_code
 * @property array|null $referrer
 * @property int $count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog applyFilters(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog applySorts(array $sorts = [])
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog dropdown(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog like(string $column, string $value, string $side = 'both', string $boolean = 'and')
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog listFrontEnd(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog lists(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog newModelQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog newQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog orLike(string $column, string $value, string $side = 'both')
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog orSearch(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog query()
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog search(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog whereCount($value)
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog whereCreatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog whereId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog whereReferrer($value)
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog whereStatusCode($value)
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog whereUpdatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|RequestLog whereUrl($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class RequestLog extends Model
{
    use Prunable;

    /**
     * @var string The database table name
     */
    protected $table = 'request_logs';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'referrer' => 'json',
    ];

    public static function createLog($statusCode = 404)
    {
        if (!Igniter::hasDatabase()) {
            return;
        }

        if (!setting('enable_request_log', true)) {
            return;
        }

        $url = Request::fullUrl();
        $referrer = Request::header('referer');

        $record = self::firstOrNew([
            'url' => substr($url, 0, 191),
            'status_code' => $statusCode,
        ]);

        if (strlen($referrer)) {
            $referrers = (array)$record->referrer ?: [];
            $referrers[] = $referrer;
            $record->referrer = $referrers;
        }

        $record->addLog();

        return $record;
    }

    public function addLog()
    {
        if (!$this->exists) {
            $this->count = 1;
            $this->save();
        } else {
            $this->increment('count');
        }

        return $this;
    }

    //
    // Concerns
    //

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<=', now()->subDays(setting('activity_log_timeout', 60)));
    }
}
