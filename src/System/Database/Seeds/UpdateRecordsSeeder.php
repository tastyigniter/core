<?php

namespace Igniter\System\Database\Seeds;

use Igniter\Cart\Models\Category;
use Igniter\Local\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fill newly created permalink_slug column with values from permalinks table
 * Truncate the permalinks table
 */
class UpdateRecordsSeeder extends Seeder
{
    /**
     * Run the demo schema seeds.
     * @return void
     */
    public function run()
    {
        $this->updateMorphsOnStatusHistory();

        $this->fixPermalinkSlugColumns();

        $this->fillColumnsOnMailTemplatesData();

        $this->updateDiskColumnOnMediaAttachments();
    }

    protected function updateMorphsOnStatusHistory()
    {
        $types = [\Igniter\Cart\Models\Order::class, \Igniter\Reservation\Models\Reservation::class];
        if (DB::table('status_history')->whereIn('object_type', $types)->count()) {
            return;
        }

        $morphs = [
            'order' => \Igniter\Cart\Models\Order::class,
            'reserve' => \Igniter\Reservation\Models\Reservation::class,
        ];

        DB::table('status_history')->get()->each(function ($model) use ($morphs) {
            $status = DB::table('statuses')->where('status_id', $model->status_id)->first();
            if (!$status || !isset($morphs[$status->status_for])) {
                return false;
            }

            DB::table('status_history')->where('status_history_id', $model->status_history_id)->update([
                'object_type' => $morphs[$status->status_for],
            ]);
        });
    }

    protected function fixPermalinkSlugColumns()
    {
        Category::whereNull('permalink_slug')->get()->each->save();
        Location::whereNull('permalink_slug')->get()->each->save();
    }

    protected function fillColumnsOnMailTemplatesData()
    {
        DB::table('mail_templates')->update(['is_custom' => 1]);
    }

    protected function updateDiskColumnOnMediaAttachments()
    {
        DB::table('media_attachments')
            ->where('disk', 'media')
            ->update(['disk' => 'public']);
    }
}
