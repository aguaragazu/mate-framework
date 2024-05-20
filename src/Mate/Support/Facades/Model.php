<?php

namespace Mate\Support\Facades;

use Mate\Database\Model as DatabaseModel;

/**
 * @method static setConnection($driver)
 * @method static \Mate\Database\Model query()
 * @method static \Mate\Database\Model selectOne(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static \Mate\Database\Model selectFromWriteConnection(string $query, array $bindings = [])
 * @method static \Mate\Database\Model select(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static \Mate\Database\Model insert(string $query, array $bindings = [])
 * @method static \Mate\Database\Model update(string $query, array $bindings = [])
 * @method static \Mate\Database\Model delete(string $query, array $bindings = [])
 * @method static \Mate\Database\Model statement(string $query, array $bindings = [])
 * @method static \Mate\Database\Model affectingStatement(string $query, array $bindings = [])
 * @method static \Mate\Database\Model unprepared(string $query)
 * @method static \Mate\Database\Model pretend(\Closure $callback)
 * @method static \Mate\Database\Model bindValues(\PDOStatement $statement, array $bindings)
 * @method static array prepareBindings(array $bindings)
 * @method static \PDO getPdo()
 * @method static void beginTransaction()
 * @method static void commit()
 * @method static void rollBack(int|null $toLevel = null)
 */
class Model extends Facade
{
    /**
     * Get the registered name of the facade.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return DatabaseModel::class;
    }


    // protected static $modelClass;

    // public static function setModel($modelClass)
    // {
    //     static::$modelClass = $modelClass;
    // }

    // protected static function getModelClass()
    // {
    //     if (!static::$modelClass) {
    //         throw new \Exception('No model class has been set for the Model facade.');
    //     }

    //     return static::$modelClass;
    // }

    // public static function __callStatic($method, $arguments)
    // {
    //     $model = new (static::getModelClass());
    //     return call_user_func_array([$model, $method], $arguments);
    // }

    // public function __toString()
    // {
    //     // Here, you might convert the object to its JSON representation
    //     // But this method should be in models if they need custom string behavior
    //     return '{}'; // default empty object
    // }
}
