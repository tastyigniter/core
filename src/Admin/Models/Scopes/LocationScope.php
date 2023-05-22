<?php

namespace Igniter\Admin\Models\Scopes;

use Igniter\Flame\Database\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LocationScope extends Scope
{
    public function addApplyPosition()
    {
        return function (Builder $builder, array $position) {
            return $builder->selectDistance($position['latitude'], $position['longitude']);
        };
    }

    public function addSelectDistance()
    {
        return function (Builder $builder, $latitude = null, $longitude = null) {
            if (setting('distance_unit') === 'km') {
                $sql = '( 6371 * acos( cos( radians(?) ) * cos( radians( location_lat ) ) *';
            } else {
                $sql = '( 3959 * acos( cos( radians(?) ) * cos( radians( location_lat ) ) *';
            }

            $sql .= ' cos( radians( location_lng ) - radians(?) ) + sin( radians(?) ) *';
            $sql .= ' sin( radians( location_lat ) ) ) ) AS distance';

            return $builder->select(DB::raw($sql), [$latitude, $longitude, $latitude]);
        };
    }
}
