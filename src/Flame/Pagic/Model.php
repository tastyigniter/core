<?php

namespace Igniter\Flame\Pagic;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use Igniter\Flame\Pagic\Contracts\TemplateInterface;
use Igniter\Flame\Support\Extendable;
use Igniter\Flame\Traits\EventEmitter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Model class.
 */
abstract class Model extends Extendable implements Arrayable, ArrayAccess, Jsonable, JsonSerializable, TemplateInterface
{
    use Concerns\GuardsAttributes;
    use Concerns\HasAttributes;
    use Concerns\HasEvents;
    use Concerns\HidesAttributes;
    use Concerns\ManagesCache;
    use Concerns\ManagesSource;
    use EventEmitter;

    public const DIR_NAME = '';

    public const DEFAULT_EXTENSION = 'blade.php';

    public static ?Dispatcher $dispatcher;

    /**
     * The array of booted models.
     */
    protected static array $booted = [];

    /**
     * The array of booted events.
     */
    protected static array $eventsBooted = [];

    /**
     * The accessors to append to the model's array form.
     */
    protected array $appends = [];

    /**
     * Indicates if the model exists.
     */
    public bool $exists = false;

    /**
     * Create a new Halcyon model instance.
     */
    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();

        $this->bootNicerEvents();

        parent::__construct();

        $this->syncOriginal();

        $this->fill($attributes);
    }

    /**
     * Check if the model needs to be booted and if so, do it.
     */
    protected function bootIfNotBooted(): void
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            $this->fireModelEvent('booting', false);

            static::boot();

            $this->fireModelEvent('booted', false);
        }
    }

    /**
     * The "booting" method of the model.
     */
    protected static function boot(): void
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the model.
     */
    protected static function bootTraits(): void
    {
        $class = static::class;

        foreach (class_uses_recursive($class) as $trait) {
            if (method_exists($class, $method = 'boot'.class_basename($trait))) {
                forward_static_call([$class, $method]);
            }
        }
    }

    /**
     * Clear the list of booted models so they will be re-booted.
     */
    public static function clearBootedModels(): void
    {
        static::$booted = [];
    }

    /**
     * Create a collection of models from plain arrays.
     */
    public static function hydrate(array $items, ?string $source = null): Collection
    {
        $instance = new static;
        $instance->setSource($source);

        $items = array_map(function($item) use ($instance) {
            return $instance->newFromFinder($item);
        }, $items);

        return $instance->newCollection($items);
    }

    /**
     * Save a new model and return the instance.
     */
    public static function create(array $attributes = []): static
    {
        $model = new static($attributes);

        $model->save();

        return $model;
    }

    /**
     * Begin querying the model.
     */
    public static function query(): Finder
    {
        return (new static)->newFinder();
    }

    /**
     * Get all of the models from the source.
     */
    public static function all(): Collection
    {
        return (new static)->newFinder()->get();
    }

    /**
     * Fill the model with an array of attributes.
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes): static
    {
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Returns the unique id of this object.
     * ex. account/login.blade.php => account-login
     * @return string
     */
    public function getId()
    {
        return str_replace(DIRECTORY_SEPARATOR, '-', $this->getBaseFileName());
    }

    /**
     * Get the value of the model's primary key.
     */
    public function getKey(): mixed
    {
        return str_replace(DIRECTORY_SEPARATOR, '.', $this->getBaseFileName());
    }

    /**
     * Get a new file finder for the object
     */
    public function newFinder(): Finder
    {
        $source = $this->getSource();

        $finder = new Finder($source, $source->getProcessor());

        return $finder->setModel($this);
    }

    /**
     * Create a new Collection instance.
     */
    public function newCollection(array $models = []): Collection
    {
        return new Collection($models);
    }

    /**
     * Create a new instance of the given model.
     */
    public function newInstance(array $attributes = [], bool $exists = false): static
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Pagic query finder instances.
        $model = new static($attributes);

        $model->exists = $exists;

        return $model;
    }

    /**
     * Create a new model instance that is existing.
     */
    public function newFromFinder(array $attributes = [], ?string $source = null): static
    {
        $instance = $this->newInstance([], true);

        $instance->setRawAttributes($attributes, true);

        $instance->setSource($source ?: $this->source);

        $instance->fireModelEvent('retrieved', false);

        return $instance;
    }

    /**
     * Update the model in the database.
     */
    public function update(array $attributes = []): bool|int
    {
        if (!$this->exists) {
            return $this->newFinder()->update($attributes);
        }

        return $this->fill($attributes)->save();
    }

    /**
     * Save the model to the source.
     */
    public function save(array $options = []): bool
    {
        return $this->saveInternal(['force' => false] + $options);
    }

    /**
     * Save the model to the database. Is used by {@link save()} and {@link forceSave()}.
     */
    public function saveInternal(array $options = []): bool
    {
        // Event
        if ($this->fireEvent('model.saveInternal', [$this->attributes, $options], true) === false) {
            return false;
        }

        $query = $this->newFinder();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        if ($this->exists) {
            $saved = $this->performUpdate($query, $options);
        } else {
            $saved = $this->performInsert($query, $options);
        }

        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    /**
     * Finish processing on a successful save operation.
     */
    protected function finishSave(array $options): void
    {
        $this->fireModelEvent('saved', false);

        $this->mTime = $this->newFinder()->lastModified();

        $this->syncOriginal();
    }

    /**
     * Perform a model update operation.
     */
    protected function performUpdate(Finder $query, array $options = []): bool
    {
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            // If the updating event returns false, we will cancel the update operation so
            // developers can hook Validation systems into their models and cancel this
            // operation if the model does not pass validation. Otherwise, we update.
            if ($this->fireModelEvent('updating') === false) {
                return false;
            }

            $dirty = $this->getDirty();

            if (count($dirty) > 0) {
                $query->update($dirty);

                $this->fireModelEvent('updated', false);
            }
        }

        return true;
    }

    /**
     * Perform a model insert operation.
     */
    protected function performInsert(Finder $query, array $options = []): bool
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // Ensure the settings attribute is passed through so this distinction
        // is recognised, mainly by the processor.
        $attributes = $this->attributesToArray();

        $query->insert($attributes);

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * Delete the model from the database.
     */
    public function delete(): bool
    {
        if (is_null($this->fileName)) {
            throw new InvalidArgumentException('No file name (fileName) defined on model.');
        }

        if ($this->exists) {
            if ($this->fireModelEvent('deleting') === false) {
                return false;
            }

            $this->performDeleteOnModel();

            $this->exists = false;

            // Once the model has been deleted, we will fire off the deleted event so that
            // the developers may hook into post-delete operations. We will then return
            // a boolean true as the delete is presumably successful on the database.
            $this->fireModelEvent('deleted', false);

            return true;
        }

        return false;
    }

    /**
     * Perform the actual delete query on this model instance.
     */
    protected function performDeleteOnModel(): void
    {
        $this->newFinder()->delete();
    }

    public static function addGlobalScope($scope, ?Closure $implementation = null) {}

    /**
     * Convert the model to its string representation.
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    public function __call(string $name, ?array $params): mixed
    {
        try {
            return parent::__call($name, $params);
        } catch (BadMethodCallException) {
            $finder = $this->newFinder();

            return call_user_func_array([$finder, $name], $params);
        }
    }

    public static function __callStatic(string $name, ?array $params): mixed
    {
        return call_user_func_array([new static, $name], $params);
    }

    public function __isset(string $key)
    {
        return isset($this->attributes[$key]) || isset($this->attributes['settings'][$key]) ||
            (
                $this->hasGetMutator($key) &&
                !is_null($this->getAttribute($key))
            );
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->$offset);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return $this->attributesToArray();
    }

    /**
     * Convert the model instance to JSON.
     */
    public function toJson($options = 0): string|false
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
