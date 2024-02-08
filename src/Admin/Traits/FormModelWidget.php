<?php

namespace Igniter\Admin\Traits;

use Exception;
use Igniter\Admin\Classes\FormField;
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
    protected array $modelsToSave = [];

    public function createFormModel(): Model
    {
        if (!$this->modelClass) {
            throw new FlashException(sprintf(lang('igniter::admin.alert_missing_field_property'), get_class($this)));
        }

        $class = $this->modelClass;

        return new $class;
    }

    public function findFormModel(string $recordId): Model
    {
        throw_unless(strlen($recordId = strip_tags($recordId)),
            new FlashException(lang('igniter::admin.form.missing_id'))
        );

        $model = $this->createFormModel();

        // Prepare query and find model record
        $query = $model->newQuery();

        /** @var Model $result */
        throw_unless($result = $query->find($recordId),
            new FlashException(sprintf(lang('igniter::admin.form.record_not_found_in_model'), $recordId, get_class($model)))
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
        try {
            return $this->formField->resolveModelAttribute($this->model, $attribute);
        } catch (Exception) {
            throw new FlashException(sprintf(lang('igniter::admin.alert_missing_model_definition'),
                $this->model::class, $attribute
            ));
        }
    }

    /** Returns the model of a relation type. */
    protected function getRelationModel(): Model
    {
        [$model, $attribute] = $this->resolveModelAttribute($this->valueFrom);

        if (!$model || !$this->hasModelRelation($model, $attribute)) {
            throw new FlashException(sprintf(lang('igniter::admin.alert_missing_model_definition'),
                $this->model::class, $this->valueFrom
            ));
        }

        return $this->makeModelRelation($model, $attribute);
    }

    protected function getRelationObject(): Relation
    {
        [$model, $attribute] = $this->resolveModelAttribute($this->valueFrom);

        if (!$model || !$this->hasModelRelation($model, $attribute)) {
            throw new FlashException(sprintf(lang('igniter::admin.alert_missing_model_definition'),
                $this->model::class, $this->valueFrom
            ));
        }

        return $model->{$attribute}();
    }

    protected function getRelationType(): string
    {
        [$model, $attribute] = $this->resolveModelAttribute($this->valueFrom);

        return $this->getModelRelationType($model, $attribute);
    }

    protected function prepareModelsToSave(?Model $model, mixed $saveData): array
    {
        $this->modelsToSave = [];
        $this->setModelAttributes($model, $saveData);

        return $this->modelsToSave;
    }

    /** Sets a data collection to a model attributes, relations will also be set. */
    protected function setModelAttributes(?Model $model, mixed $saveData)
    {
        if (!is_array($saveData) || !$model) {
            return;
        }

        $this->modelsToSave[] = $model;

        $singularTypes = ['belongsTo', 'hasOne', 'morphTo', 'morphOne'];
        foreach ($saveData as $attribute => $value) {
            $isNested = ($attribute == 'pivot' || (
                $this->hasModelRelation($model, $attribute) &&
                in_array($this->getModelRelationType($model, $attribute), $singularTypes)
            ));

            if ($isNested && is_array($value)) {
                $this->setModelAttributes($model->{$attribute}, $value);
            } elseif ($value !== FormField::NO_SAVE_DATA) {
                if (!starts_with($attribute, '_')) {
                    $model->{$attribute} = $value;
                }
            }
        }
    }

    protected function hasModelRelation(Model $model, string $attribute): bool
    {
        if (method_exists($model, 'hasRelation')) {
            return $model->hasRelation($attribute);
        }

        return method_exists($model, $attribute);
    }

    protected function makeModelRelation(Model $model, string $attribute): mixed
    {
        if (method_exists($model, 'makeRelation')) {
            return $model->makeRelation($attribute);
        }

        return $model->{$attribute}()->getModel();
    }

    protected function getModelRelationType(Model $model, int|string $attribute)
    {
        if (method_exists($model, 'getRelationType')) {
            return $model->getRelationType($attribute);
        }

        throw new \LogicException('Model does not implement getRelationType method');
    }
}
