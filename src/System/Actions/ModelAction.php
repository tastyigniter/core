<?php

namespace Igniter\System\Actions;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Traits\ExtensionTrait;
use Igniter\System\Traits\ConfigMaker;

/**
 * Model Action base Class
 */
class ModelAction
{
    use ConfigMaker;
    use ExtensionTrait;

    /** Reference to the controller associated to this action */
    protected ?Model $model;

    /** Properties that must exist in the controller using this action. */
    protected array $requiredProperties = [];

    public function __construct(?Model $model = null)
    {
        $this->model = $model;

        foreach ($this->requiredProperties as $property) {
            if (!isset($model->{$property})) {
                throw new \LogicException(sprintf(
                    'Class %s must define property %s used by %s',
                    $model::class, $property, get_called_class()
                ));
            }
        }
    }
}
