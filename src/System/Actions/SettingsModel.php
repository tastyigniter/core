<?php

namespace Igniter\System\Actions;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Igniter;

/**
 * Settings model extension
 * Based on October/ModelBehaviour
 * Usage:
 * In the model class definition:
 *   public $implement = [\Igniter\System\Actions\SettingsModel::class];
 *   public $settingsCode = 'owner_extension_settings';
 *   public $settingsFieldsConfig = 'Settings';
 */
class SettingsModel extends ModelAction
{
    protected $recordCode;

    protected $fieldConfig;

    protected $fieldValues = [];

    /**
     * @var array Internal cache of model objects.
     */
    private static $instances = [];

    protected $requiredProperties = ['settingsFieldsConfig', 'settingsCode'];

    /**
     * Constructor
     *
     * @param \Igniter\Flame\Database\Model $model
     */
    public function __construct(Model $model)
    {
        parent::__construct($model);

        $this->model->setTable('extension_settings');
        $this->model->setKeyName('id');
        $this->model->addCasts(['data' => 'array']);
        $this->model->guard([]);
        $this->model->timestamps = false;

        $parts = explode('\\', strtolower(get_class($model)));
        $namespace = implode('.', array_slice($parts, 0, 2));

        $this->configPath[] = $namespace.'::models';
        $this->configPath[] = 'igniter::models/admin';
        $this->configPath[] = 'igniter::models/system';
        $this->configPath[] = 'igniter::models/main';

        // Access to model's overrides is unavailable, using events instead
        $this->model->bindEvent('model.afterFetch', [$this, 'afterModelFetch']);
        $this->model->bindEvent('model.beforeSave', [$this, 'beforeModelSave']);
        $this->model->bindEvent('model.afterSave', [$this, 'afterModelSave']);
        $this->model->bindEvent('model.setAttribute', [$this, 'setSettingsValue']);
        $this->model->bindEvent('model.saveInternal', [$this, 'saveModelInternal']);

        /*
         * Parse the config
         */
        $this->recordCode = $this->model->settingsCode;
    }

    /**
     * Create an instance of the settings model, intended as a static method
     */
    public function instance()
    {
        if (isset(self::$instances[$this->recordCode])) {
            return self::$instances[$this->recordCode];
        }

        if (!$item = $this->getSettingsRecord()) {
            $this->model->initSettingsData();
            $item = $this->model;
        }

        return self::$instances[$this->recordCode] = $item;
    }

    /**
     * Reset the settings to their defaults, this will delete the record model
     */
    public function resetDefault()
    {
        if ($record = $this->getSettingsRecord()) {
            $record->delete();
            unset(self::$instances[$this->recordCode]);
        }
    }

    /**
     * Checks if the model has been set up previously, intended as a static method
     * @return bool
     */
    public function isConfigured()
    {
        return Igniter::hasDatabase() && $this->getSettingsRecord() !== null;
    }

    /**
     * Returns the raw Model record that stores the settings.
     * @return Model
     */
    public function getSettingsRecord()
    {
        $record = $this->model->where('item', $this->recordCode)->first();

        return $record ?: null;
    }

    /**
     * Set a single or array key pair of values, intended as a static method
     */
    public function set($key, $value = null)
    {
        $data = is_array($key) ? $key : [$key => $value];
        $obj = self::instance();
        $obj->fill($data);

        return $obj->save();
    }

    /**
     * Helper for getSettingsValue, intended as a static method
     */
    public function get($key, $default = null)
    {
        return $this->instance()->getSettingsValue($key, $default);
    }

    /**
     * Get a single setting value, or return a default value
     */
    public function getSettingsValue($key, $default = null)
    {
        if ($this->model->hasGetMutator($key))
            return $this->model->getAttribute($key);

        if (array_key_exists($key, $this->fieldValues)) {
            return $this->fieldValues[$key];
        }

        return $default;
    }

    /**
     * Set a single setting value, if allowed.
     */
    public function setSettingsValue($key, $value)
    {
        if ($this->isKeyAllowed($key)) {
            return;
        }

        $this->fieldValues[$key] = $value;
    }

    /**
     * Default values to set for this model, override
     */
    public function initSettingsData()
    {
    }

    /**
     * Populate the field values from the database record.
     */
    public function afterModelFetch()
    {
        $this->fieldValues = $this->model->data ?: [];
        $this->model->setRawAttributes(array_merge($this->fieldValues, $this->model->getAttributes()));
    }

    /**
     * Internal save method for the model
     * @return void
     */
    public function saveModelInternal()
    {
        // Purge the field values from the attributes
        $this->model->setRawAttributes(array_diff_key($this->model->getAttributes(), $this->fieldValues));
    }

    /**
     * Before the model is saved, ensure the record code is set
     * and the jsonable field values
     */
    public function beforeModelSave()
    {
        $this->model->item = $this->recordCode;
        if ($this->fieldValues) {
            $this->model->data = $this->fieldValues;
        }
    }

    /**
     * After the model is saved, clear the cached query entry.
     * @return void
     */
    public function afterModelSave()
    {
    }

    /**
     * Checks if a key is legitimate or should be added to
     * the field value collection
     */
    protected function isKeyAllowed($key)
    {
        // core columns
        if ($key == 'id' || $key == 'item' || $key == 'data') {
            return true;
        }

        // relations
        if ($this->model->hasRelation($key)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the field configuration used by this model.
     */
    public function getFieldConfig()
    {
        if ($this->fieldConfig !== null) {
            return $this->fieldConfig;
        }

        return $this->fieldConfig = $this->loadConfig($this->model->settingsFieldsConfig, ['form'], 'form');
    }

    /**
     * Returns a cache key for this record.
     */
    protected function getCacheKey()
    {
        return 'extensions::settings.'.$this->recordCode;
    }

    /**
     * Clears the internal memory cache of model instances.
     * @return void
     */
    public static function clearInternalCache()
    {
        static::$instances = [];
    }
}
