<?php

namespace Igniter\Main\Template\Concerns;

trait UsesBlueprint
{
    /**
     * Boot the sortable trait for this model.
     *
     * @return void
     */
    public static function bootUsesBlueprint()
    {
        static::retrieved(function (self $model) {
            if (is_null($model->getAttribute('settings')) && !is_null($model->getAttribute('markup'))) {
                $model->setAttribute('settings', $model->readSettings())->syncOriginal();
            }
        });

        static::saved(function (self $model) {
            $model->restorePurgedValues();

            $model->updateSettings();
        });

        static::deleted(function (self $model) {
            $model->deleteSettings();
        });
    }

    public function readSettings(): ?array
    {
        return array_get($this->getSource()->loadBlueprint(), $this->getTypeDirName().'.'.$this->getId(), []);
    }

    public function updateSettings(): bool
    {
        $settings = $this->attributes['settings'];
        $allSettings = $this->getSource()->loadBlueprint();

        if ($settings === array_get($allSettings, $this->getTypeDirName().'.'.$this->getId(), false)) {
            return false;
        }

        array_set($allSettings, $this->getTypeDirName().'.'.$this->getId(), $settings);

        return $this->getSource()->writeBlueprint($allSettings);
    }

    public function deleteSettings(): bool
    {
        $allSettings = $this->getSource()->loadBlueprint();

        if (!array_key_exists($this->getTypeDirName(), $allSettings)) {
            return false;
        }

        array_forget($allSettings, $this->getTypeDirName().'.'.$this->getId());

        return $this->getSource()->writeBlueprint($allSettings);
    }
}
