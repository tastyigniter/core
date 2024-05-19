<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $widgetsMap = [
        \System\DashboardWidgets\Activities::class => ['recent-activities'],
        \System\DashboardWidgets\Cache::class => ['cache'],
        \System\DashboardWidgets\News::class => ['news'],
        \Admin\DashboardWidgets\Onboarding::class => ['onboarding'],
        \Admin\DashboardWidgets\Statistics::class => ['stats', 'card'],
        \Admin\DashboardWidgets\Charts::class => ['charts', 'dataset'],
    ];

    public function up()
    {
        $widgets = DB::table('admin_user_preferences')
            ->where('item', 'admin_dashboardwidgets_dashboard')
            ->value('value');

        $widgets = collect(json_decode($widgets, true))->mapWithKeys(function($properties, $alias) {
            if ($options = array_get($this->widgetsMap, array_pull($properties, 'class', ''))) {
                $properties['widget'] = $options[0];

                $props = array_pull($properties, 'config');
                if (isset($options[1], $props['context'])) {
                    $properties[$options[1]] = array_pull($props, 'context');
                }

                $properties = array_merge($properties, $props);
            }

            return [$alias => $properties];
        })->all();

        DB::table('admin_user_preferences')
            ->where('item', 'admin_dashboardwidgets_dashboard')
            ->update(['value' => $widgets]);
    }
};
