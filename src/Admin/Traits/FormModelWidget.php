<?php

namespace Igniter\Admin\Traits;

use Igniter\Flame\Exception\FlashException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Form Model Widget Trait
 *
 * Special logic for form widgets that use a database stored model.
 */
trait FormModelWidget
{
    use PopulatesModelAttributes;

    public function createFormModel(): Model
    {
        if (!$this->modelClass) {
            throw new FlashException(sprintf(lang('igniter::admin.alert_missing_field_property'), $this::class));
        }

        $class = $this->modelClass;

        return new $class;
    }

    public function findFormModel(string $recordId): Model
    {
        throw_unless(strlen($recordId = strip_tags($recordId)),
            new FlashException(lang('igniter::admin.form.missing_id')),
        );

        $model = $this->createFormModel();

        // Prepare query and find model record
        $query = $model->newQuery();

        /** @var Model $result */
        throw_unless($result = $query->find($recordId),
            new FlashException(sprintf(lang('igniter::admin.form.record_not_found_in_model'), $recordId, get_class($model))),
        );

        return $result;
    }

    /**
     * Returns the final model and attribute name of
     * a nested HTML array attribute.
     * Eg: list($model, $attribute) = $this->resolveModelAttribute($this->valueFrom);
     */
    public function resolveModelAttribute(?string $attribute = null): array
    {
        return $this->formField->resolveModelAttribute($this->model, $attribute);
    }

    /** Returns the model of a relation type. */
    protected function getRelationModel(): Model
    {
        [$model, $attribute] = $this->resolveModelAttribute($this->valueFrom);

        if (!$model || !$this->hasModelRelation($model, $attribute)) {
            throw new FlashException(sprintf(lang('igniter::admin.alert_missing_model_definition'),
                $this->model::class, $this->valueFrom,
            ));
        }

        return $this->makeModelRelation($model, $attribute);
    }

    protected function getRelationObject(): Relation
    {
        [$model, $attribute] = $this->resolveModelAttribute($this->valueFrom);

        if (!$model || !$this->hasModelRelation($model, $attribute)) {
            throw new FlashException(sprintf(lang('igniter::admin.alert_missing_model_definition'),
                $this->model::class, $this->valueFrom,
            ));
        }

        return $model->{$attribute}();
    }

    protected function getRelationType(): string
    {
        [$model, $attribute] = $this->resolveModelAttribute($this->valueFrom);

        return $this->getModelRelationType($model, $attribute);
    }

    protected function makeModelRelation(Model $model, string $attribute): mixed
    {
        if (method_exists($model, 'makeRelation')) {
            return $model->makeRelation($attribute);
        }

        return $model->{$attribute}()->getModel();
    }
}
