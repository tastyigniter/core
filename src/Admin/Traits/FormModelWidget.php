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
 * Special logic for for form widgets that use a database stored model.
 */
trait FormModelWidget
{
    protected array $modelsToSave = [];

    public function createFormModel(): Model
    {
        if (!$this->modelClass) {
            throw FlashException::error(sprintf(lang('igniter::admin.alert_missing_field_property'), get_class($this)));
        }

        $class = $this->modelClass;

        return new $class;
    }

    public function findFormModel(string $recordId): Model
    {
        throw_unless(strlen($recordId = strip_tags($recordId)),
            FlashException::error(lang('igniter::admin.form.missing_id'))
        );

        $model = $this->createFormModel();

        // Prepare query and find model record
        $query = $model->newQuery();

        throw_unless($result = $query->find($recordId),
            FlashException::error(sprintf(lang('igniter::admin.form.record_not_found_in_model'), $recordId, get_class($model)))
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
            throw FlashException::error(sprintf(lang('igniter::admin.alert_missing_model_definition'),
                $this->model::class, $attribute
            ));
        }
    }

    /** Returns the model of a relation type. */
    protected function getRelationModel(): Model
    {
        [$model, $attribute] = $this->resolveModelAttribute($this->valueFrom);

        if (!$model || !$model->hasRelation($attribute)) {
            throw FlashException::error(sprintf(lang('igniter::admin.alert_missing_model_definition'),
                $this->model::class, $this->valueFrom
            ));
        }

        return $model->makeRelation($attribute);
    }

    protected function getRelationObject(): Relation
    {
        [$model, $attribute] = $this->resolveModelAttribute($this->valueFrom);

        if (!$model || !$model->hasRelation($attribute)) {
            throw FlashException::error(sprintf(lang('igniter::admin.alert_missing_model_definition'),
                $this->model::class, $this->valueFrom
            ));
        }

        return $model->{$attribute}();
    }

    protected function getRelationType(): string
    {
        [$model, $attribute] = $this->resolveModelAttribute($this->valueFrom);

        return $model->getRelationType($attribute);
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
                $model->hasRelation($attribute) &&
                in_array($model->getRelationType($attribute), $singularTypes)
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
}
