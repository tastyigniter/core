<?php

namespace Igniter\Admin\Console\Commands;

use Igniter\Admin\Classes\UserState;
use Igniter\Admin\Models\UserPreference;
use Illuminate\Console\Command;

class ClearUserStateCommand extends Command
{
    protected $signature = 'igniter:user-state-clear';

    protected $description = 'Clear expired user custom away status';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        UserPreference::query()
            ->where('item', UserState::USER_PREFERENCE_KEY)
            ->where('value->status', UserState::CUSTOM_STATUS)
            ->where('value->clearAfterMinutes', '!=', 0)
            ->get()
            ->each(function ($preference) {
                $state = json_decode($preference->value);
                if (!$state->clearAfterMinutes) {
                    return true;
                }

                if (now()->lessThan(make_carbon($state->updatedAt)->addMinutes($state->clearAfterMinutes))) {
                    return true;
                }

                UserPreference::query()
                    ->where('id', $preference->id)
                    ->update(['value' => json_encode((new static)->defaultStateConfig)]);
            });
    }
}
