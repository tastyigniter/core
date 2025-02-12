<?php

namespace Igniter\Tests\Fixtures\Models;

use Igniter\Admin\Models\StatusHistory;
use Illuminate\Database\Eloquent\Model;

class IlluminateModel extends Model
{
    protected $table = 'statuses';

    public function status_history()
    {
        return $this->hasMany(StatusHistory::class);
    }
}
