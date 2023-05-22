<?php

namespace Igniter\Admin\Console\Commands;

use Igniter\Admin\Jobs\AllocateAssignable;
use Igniter\Admin\Models\AssignableLog;
use Illuminate\Console\Command;

class AllocatorCommand extends Command
{
    protected $signature = 'igniter:assignable-allocate';

    protected $description = 'Allocate assignables to assignees';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!$availableSlotCount = AllocatorCommand::countAvailableSlot()) {
            return;
        }

        AssignableLog::getUnAssignedQueue($availableSlotCount)
            ->lazy()
            ->each(fn($assignableLog) => AllocateAssignable::dispatch($assignableLog));
    }

    public static function addSlot($slot)
    {
        $slots = (array)params('allocator_slots', []);
        if (!is_array($slot)) {
            $slot = [$slot];
        }

        foreach ($slot as $item) {
            $slots[$item] = true;
        }

        params()->set('allocator_slots', $slots);
        params()->save();
    }

    public static function removeSlot($slot)
    {
        $slots = (array)params('allocator_slots', []);

        unset($slots[$slot]);

        params()->set('allocator_slots', $slots);
        params()->save();
    }

    protected static function countAvailableSlot()
    {
        $slotMaxCount = (int)params('allocator_slot_size', 10);
        $slotSize = count((array)params('allocator_slots', []));

        return ($slotSize < $slotMaxCount)
            ? $slotMaxCount - $slotSize : 0;
    }
}