<?php

namespace Igniter\Admin\Models\Scopes;

use Carbon\Carbon;
use Igniter\Flame\Database\Scope;
use Illuminate\Database\Eloquent\Builder;

class OrderScope extends Scope
{
    public function addApplyDateTimeFilter()
    {
        return function (Builder $builder, $range) {
            return $builder->whereBetweenOrderDateTime(
                Carbon::parse(array_get($range, 'startAt', false))->format('Y-m-d H:i:s'),
                Carbon::parse(array_get($range, 'endAt', false))->format('Y-m-d H:i:s')
            );
        };
    }

    public function addWhereBetweenOrderDateTime()
    {
        return function (Builder $builder, $start, $end) {
            return $builder->whereRaw('ADDTIME(order_date, order_time) between ? and ?', [$start, $end]);
        };
    }

    public function addWhereBetweenDate()
    {
        return function (Builder $builder, $dateTime) {
            return $builder->whereRaw(
                '? between DATE_SUB(ADDTIME(order_date, order_time), INTERVAL (duration - 2) MINUTE)'.
                ' and DATE_ADD(ADDTIME(order_date, order_time), INTERVAL duration MINUTE)',
                [$dateTime]
            );
        };
    }
}
