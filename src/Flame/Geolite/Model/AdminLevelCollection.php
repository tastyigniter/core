<?php

namespace Igniter\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Exception\GeoliteException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AdminLevelCollection extends Collection
{
    public const MAX_LEVEL_DEPTH = 5;

    /**
     * @param \Igniter\Flame\Geolite\Model\AdminLevel[] $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $this->validateAdminLevels($items);
    }

    protected function checkLevel(int $level)
    {
        if ($level <= 0 || $level > self::MAX_LEVEL_DEPTH) {
            throw new GeoliteException(sprintf(
                'Administrative level should be an integer in [1,%d], %d given',
                self::MAX_LEVEL_DEPTH, $level
            ));
        }
    }

    protected function validateAdminLevels(array $items): array
    {
        $levels = [];
        foreach ($items as $adminLevel) {
            $level = $adminLevel->getLevel();
            $this->checkLevel($level);

            if ($this->has($level)) {
                throw new InvalidArgumentException(sprintf(
                    'Administrative level %d is defined twice', $level
                ));
            }

            $levels[$level] = $adminLevel;
        }

        ksort($levels, SORT_NUMERIC);

        return $levels;
    }
}
