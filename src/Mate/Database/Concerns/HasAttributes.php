<?php

namespace Mate\Database\Concerns;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException as BrickMathException;
use Brick\Math\RoundingMode;
use DateTimeInterface;
use InvalidArgumentException;
use Mate\Collections\Collection;
use Mate\Support\Arrayable;
use Mate\Support\Exceptions\MathException;
use Mate\Support\Str;

trait HasAttributes
{
    /**
     * Attributes
     * @var array
     */
    public $attributes = [];
    
    /**
     * Cast attributes
     */
    protected array $cast = [];

    /**
     * Indicates whether attributes are snake cased on arrays.
     *
     * @var bool
     */
    public static $snakeAttributes = true;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat;

    /**
     * The built-in, primitive cast types supported by Eloquent.
     *
     * @var string[]
     */
    protected static $primitiveCastTypes = [
        'array',
        'bool',
        'boolean',
        'collection',
        'custom_datetime',
        'date',
        'datetime',
        'decimal',
        'double',
        'float',
        'hashed',
        'int',
        'integer',
        'json',
        'object',
        'real',
        'string',
        'timestamp',
    ];
    
    /**
     * Get the value of an attribute.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $name, $default = null): mixed
    {
        if (!isset($this->attributes[$name])) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * Set the value of an attribute.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Check if an attribute exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Get all attributes and their values.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = $this->attributes;

        foreach ($this->hidden as $attribute) {
            unset($attributes[$attribute]);
        }

        foreach ($this->timestampsAttributes as $timestamp) {
            if (isset($attributes[$timestamp])) {
                $attributes[$timestamp] = $this->formatTimestamp($attributes[$timestamp]);
            }
        }

        return $attributes;
    }

    private function formatTimestamp($value): string
    {
        if (!is_numeric($value)) {
            return $value;
        }

        return date('Y-m-d H:i:s', (int) $value);
    }

    /**
     * Set an array of attributes and their values.
     *
     * @param array $attributes
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * Cast the value of an attribute to a specific type.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function castAttribute(string $name, $value): mixed
    {
        if (isset($this->casts[$name])) {
            switch ($this->casts[$name]) {
                case 'int':
                    case 'integer':
                        return (int) $value;
                    case 'real':
                    case 'float':
                    case 'double':
                        return $this->fromFloat($value);
                    case 'decimal':
                        return $this->asDecimal($value, explode(':', $this->getCasts()[$name], 2)[1]);
                    case 'string':
                        return (string) $value;
                    case 'bool':
                    case 'boolean':
                        return (bool) $value;
                    case 'object':
                        return $this->fromJson($value, true);
                    case 'array':
                    case 'json':
                        return $this->fromJson($value);
                    case 'collection':
                        return new Collection($this->fromJson($value));
                    // case 'date':
                    //     return $this->asDate($value);
                    // case 'datetime':
                    // case 'custom_datetime':
                    //     return $this->asDateTime($value);
                    
                    // case 'timestamp':
                    //     return $this->asTimestamp($value);
            }
        }

        return $value;
    }

    /**
     * Get the attribute cast types.
     *
     * @return array
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * Set the cast types for attributes.
     *
     * @param array $casts
     * @return void
     */
    public function setCasts(array $casts): void
    {
    
    }

    // public function fillable(): array
    // {
    //     return array_diff($this->getVisible(), $this->hidden);
    // }

    public function getVisible(): array
    {
        return array_keys($this->attributes);
    }

    /**
     * Decode the given float.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function fromFloat($value)
    {
        return match ((string) $value) {
            'Infinity' => INF,
            '-Infinity' => -INF,
            'NaN' => NAN,
            default => (float) $value,
        };
    }

    /**
     * Return a decimal as string.
     *
     * @param  float|string  $value
     * @param  int  $decimals
     * @return string
     */
    protected function asDecimal($value, $decimals)
    {
        try {
            return (string) BigDecimal::of($value)->toScale($decimals, RoundingMode::HALF_UP);
        } catch (BrickMathException $e) {
            throw new MathException('Unable to cast value to a decimal.', previous: $e);
        }
    }


/**
     * Set the array of model attributes. No checking is done.
     *
     * @param  array  $attributes
     * @param  bool  $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        $this->classCastCache = [];
        $this->attributeCastCache = [];

        return $this;
    }


    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        // If an attribute is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing attributes vs. arraying / JSONing a model.
        $attributes = $this->addDateAttributesToArray(
            $attributes = $this->getArrayableAttributes()
        );

        $attributes = $this->addMutatedAttributesToArray(
            $attributes, $mutatedAttributes = $this->getMutatedAttributes()
        );

        // Next we will handle any casts that have been setup for this model and cast
        // the values to their appropriate type. If the attribute has a mutator we
        // will not perform the cast on those attributes to avoid any confusion.
        $attributes = $this->addCastAttributesToArray(
            $attributes, $mutatedAttributes
        );

        // Here we will grab all of the appended, calculated attributes to this model
        // as these attributes are not really in the attributes array, but are run
        // when we need to array or JSON the model for convenience to the coder.
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        return $attributes;
    }

    /**
     * Get the model's relationships in array form.
     *
     * @return array
     */
    public function relationsToArray()
    {
        $attributes = [];

        foreach ($this->getArrayableRelations() as $key => $value) {
            // If the values implement the Arrayable interface we can just call this
            // toArray method on the instances which will convert both models and
            // collections to their proper array form and we'll set the values.
            if ($value instanceof Arrayable) {
                $relation = $value->toArray();
            }

            // If the value is null, we'll still go ahead and set it in this list of
            // attributes, since null is used to represent empty relationships if
            // it has a has one or belongs to type relationships on the models.
            elseif (is_null($value)) {
                $relation = $value;
            }

            // If the relationships snake-casing is enabled, we will snake case this
            // key so that the relation attribute is snake cased in this returned
            // array to the developers, making this consistent with attributes.
            if (static::$snakeAttributes) {
                $key = Str::snake($key);
            }

            // If the relation value has been set, we will set it on this attributes
            // list for returning. If it was not arrayable or null, we'll not set
            // the value on the array because it is some type of invalid value.
            if (isset($relation) || is_null($value)) {
                $attributes[$key] = $relation;
            }

            unset($relation);
        }

        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable relations.
     *
     * @return array
     */
    protected function getArrayableRelations()
    {
        return $this->getArrayableItems($this->relations);
    }

    /**
     * Get an attribute array of all arrayable values.
     *
     * @param  array  $values
     * @return array
     */
    protected function getArrayableItems(array $values)
    {
        if (count($this->getVisible()) > 0) {
            $values = array_intersect_key($values, array_flip($this->getVisible()));
        }

        if (count($this->getHidden()) > 0) {
            $values = array_diff_key($values, array_flip($this->getHidden()));
        }

        return $values;
    }

    

    /**
     * Get all of the current attributes on the model for an insert operation.
     *
     * @return array
     */
    protected function getAttributesForInsert()
    {
        return $this->getAttributes();
    }
}
