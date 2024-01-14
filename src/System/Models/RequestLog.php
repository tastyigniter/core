<?php

namespace Igniter\System\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Igniter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Request;

/**
 * RequestLog Model Class
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
