<?php

namespace Mate\Database;

use Closure;
use Mate\Database\Drivers\DatabaseDriver;
use Mate\Database\Exception\DatabaseException;
use Mate\Database\Exception\ModelNotFoundException;
use Mate\Database\Relations\Relation;
use Mate\Database\Traits\QueryBuilder;
use Mate\Support\Arr;
use Mate\Support\Arrayable;
use Mate\Support\Str;
use Mate\Support\Traits\ForwardsCalls;
use Mate\Traits\Macroable;

class Database
{
    use QueryBuilder, 
        ForwardsCalls, 
        Macroable {
            __call as macroCall;
    }

    protected ?string $table = '';
    protected $result;
    protected $selectCols;
    protected $limit;
    protected $offset;
    protected $fillableAttr;
    protected $insertKeys;
    protected $insertValues;
    protected $enableQueryLog = false;
    protected $queryLog = [];
    protected $whereOperators = ['=', '!=', '<>', '>', '>=', '<', '<=', 'like', 'LIKE'];
    protected $conditions = "";
    protected $groupBy;
    protected $having;
    protected $orderBy;
    protected $updateValues;
    protected $innerJoin;
    protected $leftJoin;
    protected $rightJoin;
    protected $where = [];
    protected $sql = "";

    /**
     * @var \Mate\Database\Drivers\DatabaseDriver
     */
    protected $connection;
    /**
     * @var \Mate\Database\Model
     */
    protected $model;

    protected $eagerRelations = [];

    public $distinct = false;
    public array $columns = ['*'];
    public $from;

    public const QUERY_SELECT = "SELECT";
    public const QUERY_INSERT = "INSERT";
    public const QUERY_UPDATE = "UPDATE";
    public const QUERY_DELETE = "DELETE";
    public const QUERY_TRUNCATE = "TRUNCATE";
    public const QUERY_INSERT_DUPLICATE = "INSERT_DUPLICATE";
    public const QUERY_EXISTS = "EXISTS";

    /**
     * Database construct
     *
     * @param string|null $driver   Database Driver
     * @param string|null $host     Database Host
     * @param string|null $user     Database User
     * @param string|null $password User Password
     * @param string|null $database Database Name
     * @param string|null $port     Database Port
     */
    // public function __construct(
    //     ?string $driver = null,
    //     ?string $host = null,
    //     ?string $port = null,
    //     ?string $database = null,
    //     ?string $user = null,
    //     ?string $password = null,
    // ) {
    //     $this->driver   = $driver ?? env('DB_CONNECTION', 'sqlite');
    //     $this->host     = $host ?? env('DB_HOST', 'localhost');
    //     $this->port     = $port ?? env('DB_PORT', '3306');
    //     $this->database = $database ?? env('DB_DATABASE', 'database/curso_framework.sqilite');
    //     $this->user     = $user ?? env('DB_USERNAME', 'root');
    //     $this->password = $password ?? env('DB_PASSWORD', 'secret');

    //     $mode = $this->driver . '/' . $this->host . '/' . $this->database;

    //     if (isset(static::$con[$mode])) {
    //         $this->connection = static::$con[$mode];
    //     }
    //     if (empty(static::$con) && !isset(static::$con[$mode])) {
    //         $this->connection = app(DatabaseDriver::class)->connect(
    //             $this->driver,
    //             $this->host,
    //             $this->port,
    //             $this->database,
    //             $this->user,
    //             $this->password
    //         );
    //         static::$con[$mode] = $this->connection;
    //     }
    // }

    public function __construct(DatabaseDriver $driver)
    {
        $this->connection = $driver;
    }
    /**
     * Handles dynamic "where" clauses to the query.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     */
    public function dynamicWhere($method, $parameters)
    {
        $finder = substr($method, 5);

        $segments = preg_split(
            '/(And|Or)(?=[A-Z])/',
            $finder,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        // The connector variable will determine which connector will be used for the
        // query condition. We will change it as we come across new boolean values
        // in the dynamic method strings, which could contain a number of these.
        $connector = 'and';

        $index = 0;

        foreach ($segments as $segment) {
            // If the segment is not a boolean connector, we can assume it is a column's name
            // and we will add it to the query as a new constraint as a where clause, then
            // we can keep iterating through the dynamic method string's segments again.
            if ($segment !== 'And' && $segment !== 'Or') {
                $this->addDynamic($segment, $connector, $parameters, $index);

                $index++;
            }

            // Otherwise, we will store the connector so we know how the next where clause we
            // find in the query should be connected to the previous ones, meaning we will
            // have the proper boolean connector to connect the next where clause found.
            else {
                $connector = $segment;
            }
        }

        return $this;
    }

    /**
     * Add a single dynamic where clause statement to the query.
     *
     * @param  string  $segment
     * @param  string  $connector
     * @param  array  $parameters
     * @param  int  $index
     * @return void
     */
    protected function addDynamic($segment, $connector, $parameters, $index)
    {
        // Once we have parsed out the columns and formatted the boolean operators we
        // are ready to add it to this query as a where clause just like any other
        // clause on the query. Then we'll increment the parameter index values.
        $bool = strtolower($connector);

        $this->where(Str::snake($segment), '=', $parameters[$index], $bool);
    }

    /**
     * Lazy loading de relaciones
     */
    public function __call($method, $arguments)
    {
        // if (method_exists($this, $method)) {
        //     return $this->$method()->getResults();
        // }

        if (static::hasMacro($method)) {
            return $this->macroCall($method, $arguments);
        }

        if (str_starts_with($method, 'where')) {
            return $this->dynamicWhere($method, $arguments);
        }

        static::throwBadMethodCallException($method);
    }

    // public static function connect(
    //     string $driver = null,
    //     string $host = null,
    //     string $port = null,
    //     string $database = null,
    //     string $user = null,
    //     string $password = null,
    //     array $config = []
    // ) {
    //     $model = new static();

    //     $model->driver = $driver ?? $config['connection'];
    //     $model->host = $host ?? $config['host'];
    //     $model->port = $port ?? $config['port'];
    //     $model->database = $database ?? $config['database'];
    //     $model->user = $user ?? $config['username'];
    //     $model->password = $password ?? $config['password'];

    //     $mode = $model->driver . '/' . $model->host . '/' . $model->database;

    //     if (isset(static::$con[$mode])) {
    //         $model->db = static::$con[$mode];
    //     }

    //     if (empty(static::$con) && !isset(static::$con[$mode])) {
    //         $model->db = app(DatabaseDriver::class)->connect(
    //             $model->driver,
    //             $model->host,
    //             $model->port,
    //             $model->database,
    //             $model->user,
    //             $model->password
    //         );
    //         static::$con[$mode] = $model->db;
    //     }
    //     return $model;
    // }

    // public static function statement(string $sql)
    // {
    //     $model = new static();
    //     $model->db = app(DatabaseDriver::class)->connect(
    //         env('DB_CONNECTION', 'sqlite'),
    //         env('DB_HOST', 'localhost'),
    //         env('DB_PORT', '3306'),
    //         env('DB_DATABASE', 'database/curso_framework.sqlite'),
    //         env('DB_USERNAME', 'root'),
    //         env('DB_PASSWORD', 'secret')
    //     );

    //     $model->db->statement($sql);
    //     return $model;
    // }

    /**
     * Metodo para solictar eager loading de las relaciones
     * 
     * @param string|array $relations
     * @return $this
     */
    public function withRelations($relations)
    {

        $this->eagerRelations = is_array($relations) ? $relations : func_get_args();
        return $this;
    }

    /**
     * Prepare SQL query
     * @param  string $type SQL query
     * @return string
     */
    protected function buildQuery($type)
    {
        $where = "";
        if (!empty($this->where)) {
            $where = "WHERE TRUE AND " . implode(' AND ', $this->where);
        }

        switch ($type) {
            case self::QUERY_TRUNCATE:
                $sql = "TRUNCATE {$this->table}";
                break;
            case self::QUERY_UPDATE:
                $sql = "UPDATE {$this->table} SET {$this->updateValues} {$where}";
                unset($where);
                break;
            case self::QUERY_EXISTS:
                $where .= !boolval($this->conditions) ? "" : $this->conditions;
                $colAs = "exists";
                $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} {$where}) as '{$colAs}'";
                unset($where);
                break;
            case self::QUERY_DELETE:
                $where .= !boolval($this->conditions) ? "" : $this->conditions;
                $sql = "DELETE FROM {$this->table} {$where}";
                unset($where);
                break;
            case self::QUERY_INSERT:
                $sql = "INSERT INTO {$this->table}({$this->insertKeys}) VALUES {$this->insertValues}";
                break;
            case self::QUERY_INSERT_DUPLICATE:
                $sql = "INSERT INTO {$this->table}({$this->insertKeys}) VALUES {$this->insertValues} ON DUPLICATE KEY UPDATE {$this->updateValues}";
                break;
            case self::QUERY_SELECT:
                $select = self::QUERY_SELECT;
                $limit = "";
                $where .= !boolval($this->conditions) ? "" : $this->conditions;
                $groupBy = $this->groupBy ? "GROUP BY " . $this->groupBy : "";
                $having = $this->having ? "HAVING " . $this->having : "";
                $orderBy = $this->orderBy ? "ORDER BY " . $this->orderBy : "";
                $innerJoin = $this->innerJoin ?? "";
                $leftJoin = $this->leftJoin ?? "";
                $rightJoin = $this->rightJoin ?? "";
                $join = $innerJoin . $leftJoin . $rightJoin;
                if (is_numeric($this->limit)) {
                    $offset = is_numeric($this->offset) ? $this->offset : 0;
                    $limit = " LIMIT {$offset}, {$this->limit}";
                }

                $columns = ($this->selectCols) ? $this->selectCols : "*";
                $sql = "{$select} {$columns} FROM {$this->table} {$join} {$where} {$groupBy} {$having} {$orderBy} {$limit}";
                
                unset($where);
                unset($groupBy);
                unset($having);
                unset($orderBy);
                unset($innerJoin);
                unset($leftJoin);
                unset($rightJoin);
                unset($join);
                unset($columns);
                unset($limit);
                break;
        }

        $this->reset();

        return $sql;
    }

    /**
     * TRUNCATE query
     * @return void
     */
    public function truncate()
    {
        if (false === $this->checkTable()) {
            return false;
        }
        $sql = $this->buildQuery(self::QUERY_TRUNCATE);
        $this->connection->statement($sql);
    }

    /**
     * DELETE query
     * @param string|int|null $id
     * @return void
     */
    public function delete($id = null)
    {
        if (false === $this->checkTable()) {
            return false;
        }
        if (!is_null($id)) {
            $this->where($this->table . '.id', '=', $id);
        }

        $sql = $this->buildQuery(self::QUERY_DELETE);
        $this->connection->delete($sql);
    }

    /**
     * UPDATE query
     * @param  array  $data
     * @return void
     */
    public function update(?array $data = null)
    {
        if (false === $this->checkTable()) {
            return false;
        }
        $newData = $this->getAttributes();
        $this->conditions = ['id',  $newData['id']];
        $this->where[] = $this->parseConditions($this->conditions);

        if (!is_null($data)) {
            if (!is_array($data)) {
                throw new DatabaseException(
                    DatabaseException::ERR_MSQ_BAD_REQUEST,
                    DatabaseException::ERR_CODE_BAD_REQUEST
                );
            }
            $newData = $data;
        }

        $this->parseUpdateValue($newData);
        $sql = $this->buildQuery(self::QUERY_UPDATE);
        $this->connection->update($sql);
    }

    /**
     * Insert or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return bool
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        if (!$this->where($attributes)->exists()) {
            return $this->insert(array_merge($attributes, $values));
        }

        if (empty($values)) {
            return true;
        }

        return (bool) $this->limit(1)->update($values);
    }

    public function exists()
    {
        if (false === $this->checkTable()) {
            return false;
        }
        $newData = $this->getAttributes();
        $this->conditions = ['id',  $newData['id']];
        $this->where[] = $this->parseConditions($this->conditions);

        $this->parseWhereCondition($newData);
        $sql = $this->buildQuery(self::QUERY_EXISTS);
        $results = $this->connection->select($sql);

        if (isset($results[0])) {
            $results = (array) $results[0];

            return (bool) $results['exists'];
        }

        return false;
    }

    /**
     * Set the table which the query is targeting.
     *
     * @param  \Closure|\Mate\Database\Builder|string  $table
     * @param  string|null  $as
     * @return $this
     */
    public function from($table, $as = null)
    {
        $this->from = $as ? "{$table} as {$as}" : $table;

        return $this;
    }

    /**
     * Determine if the value is a query builder instance or a Closure.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isQueryable($value)
    {
        return $value instanceof self ||
            $value instanceof Relation ||
            $value instanceof Closure;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param  \Mate\Database\Model  $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->from($model->getTable());

        return $this;
    }

    public function findQuery(?array $data = null)
    {
        if (false === $this->checkTable()) {
            return false;
        }

        if (!is_null($data)) {
            $this->where($data[0]);
            if (count($data) > 1) {
                foreach (array_slice($data, 1) as $condition) {
                    $this->orWhere($condition);
                }
            }
        }

        $sql = $this->buildQuery(self::QUERY_SELECT);

        return $this->connection->statement($sql);
    }

    public function find($id, $colums = ['*'])
    {
        return $this->where(['id', $id])->first($colums);
    }

    public function findOrFail($id, $colums = ['*'])
    {
        $result = $this->find($id, $colums);

        $id = $id instanceof Arrayable ? $id->toArray() : $id;

        if (is_array($id)) {
            if (count($result) !== count(array_unique($id))) {
                throw (new ModelNotFoundException)->setModel(
                    get_class($this->model),
                    array_diff($id, $result->modelKeys())
                );
            }

            return $result;
        }

        if (is_null($result)) {
            throw (new ModelNotFoundException)->setModel(
                get_class($this->model),
                $id
            );
        }

        return $result;
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string  $column
     * @return mixed
     */
    public function value($column)
    {
        $result = (array) $this->first([$column]);

        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * Parse values for Update
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function parseUpdateValue(array $data)
    {
        $values = [];
        foreach ($data as $key => $value) {
            list($key, $value) = $this->parseRawValue($key, $value);
            if (
                $key === 'id'
                || $key === 'created_at'
                || $key === 'updated_at'
                || $key === 'deleted_at'
                || $key === 'id'
            ) {
                continue;
            }
            $values[] = "`{$key}`" . ' = ' . $value;
        }
        $this->updateValues = implode(' , ', $values);

        unset($values);
    }

    /**
     * Execute the given callback while selecting the given columns.
     *
     * After running the callback, the columns are reset to the original value.
     *
     * @param  array  $columns
     * @param  callable  $callback
     * @return mixed
     */
    protected function onceWithColumns($columns, $callback)
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        $result = $callback();

        $this->columns = $original;

        return $result;
    }

    /**
     * INSERT statement
     * @param  array  $request
     * @return void
     */
    public function insert(array $request)
    {
        if (false === $this->checkTable()) {
            return false;
        }

        if (empty($values)) {
            return true;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        $this->parseValues($request);
        $sql = $this->buildQuery(self::QUERY_INSERT);

        return $this->connection->insert($sql);
    }

    /**
     * INSERT MANY
     * @param  array  $requests
     * @return void
     */
    public function insertMany(array $requests)
    {
        if (false === $this->checkTable()) {
            return false;
        }

        $tmp = [];
        if (empty($this->fillableAttr) || !is_array($this->fillableAttr)) {
            throw new DatabaseException(
                DatabaseException::ERR_MSQ_BAD_REQUEST,
                DatabaseException::ERR_CODE_BAD_REQUEST
            );
        }
        foreach ($requests as $request) {
            if (!is_array($request)) {
                throw new DatabaseException(
                    DatabaseException::ERR_MSQ_BAD_REQUEST,
                    DatabaseException::ERR_CODE_BAD_REQUEST
                );
            }
            if (empty($request)) {
                continue;
            }
            $this->parseValues($request);
            $tmp[] = $this->insertValues;
        }
        $this->insertKeys = implode(',', $this->fillableAttr);
        $this->insertValues = implode(',', $tmp);
        unset($tmp);
        $sql = $this->buildQuery(self::QUERY_INSERT);
        $this->connection->insert($sql);
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        if (false === $this->checkTable()) {
            return false;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        $this->parseValues($values);
        $sql = $this->buildQuery(self::QUERY_INSERT);

        $this->connection->insert($sql);

        $id = $this->connection->lastInsertId();

        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * INSERT DUPLICATE KEY
     * @param  array  $request
     * @return void
     */
    public function insertDuplicate(array $request)
    {
        if (false === $this->checkTable()) {
            return false;
        }
        if (!is_array($request) || is_array(isset($request[0]))) {
            throw new DatabaseException(DatabaseException::ERR_MSQ_BAD_REQUEST, DatabaseException::ERR_CODE_BAD_REQUEST);
        }

        $this->parseValues($request, false);
        $this->parseUpdateValue($request);
        $sql = $this->buildQuery(self::QUERY_INSERT_DUPLICATE);
        $this->connection->statement($sql);
    }

    /**
     * INNER JOIN
     * @return $this
     */
    public function innerJoin()
    {
        if (func_num_args() != 3) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        list($joinTable, $tableCond, $joinCond) = func_get_args();
        $this->innerJoin = " INNER JOIN {$this->escape($joinTable)} ON {$this->escape($tableCond)} = {$this->escape($joinCond)}";
        return $this;
    }

    /**
     * LEFT JOIN
     * @return $this
     */
    public function leftJoin()
    {
        if (func_num_args() != 3) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        list($joinTable, $tableCond, $joinCond) = func_get_args();
        $this->leftJoin = " LEFT JOIN {$this->escape($joinTable)} ON {$this->escape($tableCond)} = {$this->escape($joinCond)}";
        return $this;
    }

    /**
     * RIGHT JOIN
     * @return $this
     */
    public function rightJoin()
    {
        if (func_num_args() != 3) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        list($joinTable, $tableCond, $joinCond) = func_get_args();
        $this->rightJoin = " RIGHT JOIN {$this->escape($joinTable)} ON {$this->escape($tableCond)} = {$this->escape($joinCond)}";
        return $this;
    }

    /**
     * Set WHERE conditions
     * @param  mixed  $conditions
     * @return $this
     */
    public function where($conditions = [])
    {
       
        if (!is_array($conditions) || empty($conditions)) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }

        if (!is_array($conditions[0])) {
            $this->where[] = $this->parseConditions($conditions);
        } else {
            foreach ($conditions as $condition) {
                $this->where[] = $this->parseConditions($condition);
            }
        }

        return $this;
    }

    /**
     * Parse conditions
     * @param  array  $condition
     * @return mixed
     */
    public function parseConditions(array $condition)
    {
        switch (count($condition)) {
            case 1:
                throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
                break;
            case 2:
                list($key, $value) = $condition;
                list($key, $escapeValue) = $this->parseRawValue($key, $value);
                return "{$key}" . ' = ' . $escapeValue;
                break;
            case 3:
                list($key, $operator, $value) = $condition;
                list($key, $escapeValue) = $this->parseRawValue($key, $value);
                $operator = strtoupper($operator);
                return !in_array($operator, $this->whereOperators) ?: $key . " {$operator} " . $escapeValue;
                break;
        }
    }

    /**
     * Parse raw value
     * @param  string $key
     * @param  mixed $value
     * @return string
     */
    public function parseRawValue($key, $value)
    {
        preg_match("/\#(.+)/", $key, $output);
        $scapeValue = $this->escape($value ?? '');
        return $output && $output[1] ? [substr($key, 1), "{$scapeValue}"] : [$key, "{$scapeValue}"];
    }

    /**
     * Set OR where condition
     * @return $this;
     */
    public function orWhere()
    {
        if (!is_array(func_get_args()[0]) || empty(func_get_args()[0])) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }

        $this->conditions .= " OR " . $this->parseConditions(func_get_args()[0]);

        return $this;
    }

    /**
     * Set BETWEEN condition
     * @return $this
     */
    public function whereBetween(...$params)
    {
        list($key, $values) = $this->parseWhereCondition($params);
        $this->conditions .= " AND " . "{$this->escape($key)} BETWEEN " . implode(' AND ', $values);

        return $this;
    }

    /**
     * Set NOT BETWEEN condition
     * @return $this
     */
    public function whereNotBetween(...$params)
    {
        list($key, $values) = $this->parseWhereCondition($params);
        $this->conditions .= " AND " . "{$this->escape($key)} NOT BETWEEN " . implode(' AND ', $values);

        return $this;
    }

    /**
     * Set NULL condition
     * @return $this
     */
    public function whereNull($key)
    {
        $this->conditions .= " AND " . "`{$this->escape($key)}` IS NULL ";

        return $this;
    }

    /**
     * Set NOT NULL condition
     * @return $this
     */
    public function whereNotNull($key)
    {
        $this->conditions .= " AND " . "`{$this->escape($key)}` IS NOT NULL ";

        return $this;
    }

    /**
     * Parse Where condition
     * @return array
     */
    public function parseWhereCondition($condition)
    {
        list($key, $values) = $condition;

        if (count($condition) != 2 || !is_array($values)) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        $values = array_map(function ($value) {
            return "'{$this->escape($value)}'";
        }, $values);

        return [$key, $values];
    }

    /**
     * Set IN condition
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function whereIn(...$params)
    {
        list($key, $values) = $this->parseWhereCondition($params);
        $this->conditions .= " AND " . "{$this->escape($key)} IN (" . implode(', ', $values) . ")";

        return $this;
    }

    /**
     * Set NOT IN condition
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function whereNotIn(...$params)
    {
        list($key, $values) = $this->parseWhereCondition($params);
        $this->conditions .= " AND " . "{$this->escape($key)} NOT IN (" . implode(', ', $values) . ")";

        return $this;
    }

    /**
     * Check table
     * @return boolean
     */
    public function checkTable()
    {
        return (bool) $this->table;
    }

    /**
     * Set table
     * @param  string $table Table
     * @return void
     */
    public function table(string $table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Set model
     * @param  string $model Model
     * @return void
     */
    public function model(string $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * SQL ESCAPE
     * @param  mixed $data
     * @return string
     */
    public function escape($data, $binary = false)
    {
        if (is_array($data)) {
            return array_map(function ($item) {
                return $this->connection->escape($item, false);
            }, $data);
        }

        return $this->connection->escape($data, $binary);
    }

    /**
     * SQL error
     * @return string
     */
    public function error()
    {
        return $this->connection->error();
    }

    /**
     * Dump the current SQL and bindings.
     *
     * @param  mixed  ...$args
     * @return $this
     */
    public function dump(...$args)
    {
        dump(
            $this->sql,
            $this->conditions,
            ...$args,
        );

        return $this;
    }

    /**
     * Die and dump the current SQL and bindings.
     *
     * @return never
     */
    public function dd()
    {
        dd($this->sql, $this->conditions);
    }

    public function newQuery()
    {
        return new static($this->connection);
    }

    public function forSubQuery()
    {
        return $this->newQuery();
    }

    /**
     * Set SELECT columns
     * @param  array  $cols
     * @return $this
     */
    public function select($cols = [])
    {
        $columns = $this->parseRawKey($cols);
        $this->selectCols = implode(',', $columns);

        return $this;
    }

    /**
     * Parse raw key
     * @param  array  $keys
     * @return array
     */
    public function parseRawKey(array $keys)
    {
        return array_map(function ($key) {
            preg_match("/\#(.+)/", $key, $output);
            return $output && $output[1] ? "{$this->escape($output[1])}" : "`{$this->escape($key)}`";
        }, $keys);
    }

    /**
     * Set OFFSET
     * @param  mixed $offset
     * @return $this
     */
    public function offset($offset)
    {
        if (!is_int($offset)) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Set LIMIT
     * @param  mixed $limit
     * @return $this
     */
    public function limit($limit)
    {
        if (!is_int($limit)) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * Force the query to only return distinct results.
     *
     * @return $this
     */
    public function distinct()
    {
        $columns = func_get_args();

        if (count($columns) > 0) {
            $this->distinct = is_array($columns[0]) || is_bool($columns[0]) ? $columns[0] : $columns;
        } else {
            $this->distinct = true;
        }

        return $this;
    }

    /**
     * Set GROUP BY
     * @param  string $key
     * @return mixed
     */
    public function groupBy($key)
    {
        if (is_string($key)) {
            $this->groupBy = $key;
            return $this;
        }
        return false;
    }

    /**
     * Set HAVING
     * @return $this
     */
    public function having()
    {
        list($key, $operator, $value) = func_get_args();
        if (func_num_args() != 3 || !in_array($operator, ['>', '=', '<'])) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }

        preg_match("/\#(.+)/", $key, $output);
        $escapeKey = $output && $output[1] ? "{$this->escape($output[1])}" : "`{$this->escape($key)}`";
        $this->having = $escapeKey . " {$this->escape($operator)} " . "{$this->escape($value)}";
        unset($escapeKey);
        return $this;
    }

    /**
     * Set ORDER BY
     * @return $this
     */
    public function orderBy()
    {
        list($key, $sort) = func_get_args();
        if (func_num_args() != 2 || !in_array(strtoupper($sort), ['ASC', 'DESC'])) {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_ARGUMENTS);
        }

        $this->orderBy = " {$this->escape($key)} " . "{$this->escape(strtoupper($sort))}";

        return $this;
    }

    // /**
    //  * Return array responses
    //  * @param  int|null $limit
    //  * @return array
    //  */
    // public function get($limit = null)
    // {
    //     if (false === $this->checkTable()) {
    //         return false;
    //     }

    //     $this->limit = $limit ?? $this->limit;
    //     $sql = $this->buildQuery(self::QUERY_SELECT);
    //     $result = $this->connection->select($sql);

    //     if ($this->eagerRelations) {
    //         $this->loadRelations($result, $this->eagerRelations);
    //     }

    //     // return $this->resultToArray($result);
    //     return collect($result);
    // }
    public function get($columns = null)
    {
        // if (false === $this->checkTable()) {
            //     return false;
            // }
        // $this->selectCols = $columns ?? '';
        
        
        if ($this->eagerRelations) {
            $this->loadRelations($result, $this->eagerRelations);
        }

        return collect($this->onceWithColumns($columns, function () {
            $sql = $this->buildQuery(self::QUERY_SELECT);
            // $result = $this->connection->select($sql);
            // dd('database get()', $sql, $this->connection, $this, $result);
            return $this->connection->select($sql);
        }));
    }

    

    /**
     * Add a basic where clause to the query, and return the first result.
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return \Mate\Database\Model|static|null
     */
    public function firstWhere($column, $operator = null, $value = null)
    {
        $result = $this->where([$column, $operator, $value])->first();
        
        if ($result) {
            $model = new $this->model;
            foreach ($result as $key => $value) {
                $model->$key = $result->{$key};
            }
            return $model;
        }

        return null;
    }

    /**
     * Convert to array
     */
    public function resultToArray($result)
    {
        return $this->connection->select($result);
    }

    /**
     * Parse request to keys & values
     * @param  array  $request
     * @param  boolean $checkFillable
     * @return void
     */
    public function parseValues(array $request, $checkFillable = true)
    {
        $parse = $request;
        if ($checkFillable && $this->hasFillable()) {
            $tmp = array_fill_keys($this->fillableAttr, null);
            $parse = array_intersect_key($request, $tmp);
            //If total keys of request and fillable is different, keys are replace with null values
            $parse = array_replace($tmp, $parse);
        }
        $values = array_map(function ($value) {
            return !is_null($value) ? "{$this->escape($value)}" : $value;
        }, array_values($parse));
        $this->insertKeys = implode(',', array_keys($parse));
        $tmpValues = "('" . implode("','", $values) . "')";
        $this->insertValues = str_replace("''", '"', $tmpValues);

        unset($tmp);
        unset($tmpValues);
        unset($parse);
    }

    /**
     * Enable query log
     * @return void
     */
    public function enableQueryLog()
    {
        return $this->enableQueryLog = true;
    }

    /**
     * Get query log
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * Check $fillable
     * @return boolean
     */
    public function hasFillable()
    {
        return boolval($this->fillableAttr);
    }

    /**
     * Chunk data rows
     * @param  int $count
     * @param  callable $callback
     * @return bool
     */
    public function chunk($count, $callback)
    {
        $page = 1;

        do {
            $results = $this->limit($count)->offset(($page - 1) * $count)->get();
            $countResults = count($results);

            if ($countResults == 0) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            unset($results);
            $page++;
        } while ($countResults == $count);

        return true;
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * Roll back current transaction
     * @return bool
     */
    public function rollBack()
    {
        return $this->connection->rollback();
    }

    /**
     * Set Fillable
     *
     * @param array $fillable Fillable
     *
     * @return $this
     */
    public function setFillable(array $fillable)
    {
        $this->fillableAttr = $fillable;
        return $this;
    }

    protected function loadRelations(array &$models, array $relations)
    {
        foreach ($relations as $relation) {
            if (method_exists($this, $relation)) {
                // Aquí se podrían hacer optimizaciones para cargar 
                // todos los datos relacionados en una o pocas consultas
                $relationData = $this->fetchRelationData($relation, $models);

                $this->mapRelationData($models, $relationData, $relation);
            }
        }
    }

    // Método para obtener datos de la relación, 
    // por ejemplo, usando JOINs o subconsultas

    // refactoriza esta funcion para que retorne un array de relaciones

    protected function fetchRelationData($relation, $models)
    {

        $relationMethod = $this->$relation();
        $relatedModels = $relationMethod->getRelated();
        $keys = array_map(function ($model) use ($relationMethod) {
            return $model->{$relationMethod->getLocalKey()};
        }, $models);

        if (empty($keys)) {
            return [];
        }
        // Asumiendo que getRelated retorna la instancia del modelo 
        // relacionado y whereIn es un método para filtrar resultados
        $relatedTable = ($relationMethod->getRelated())->getTable();
        $foreignKey = $relationMethod->getForeignKey();
        $relatedModels = $relationMethod->getRelated();
        dd('Database->fetchRelationData(): ',$keys, $models, $foreignKey, $relatedModels);
        return $relatedModels
            ->whereIn($foreignKey, $models)
            // ->where(["$foreignKey", $models["{$this->primaryKey}"]])
            ->get();
    }

    // Método para asignar los datos relacionados a los modelos principales
    protected function mapRelationData(&$models, $relationData, $relation)
    {
        $relationMethod = $this->$relation();
        $keyedRelationData = [];

        foreach ($relationData as $related) {
            $keyedRelationData[$related->{$relationMethod->getForeignKey()}] = $related;
        }

        foreach ($models as $model) {
            $modelKey = $model->{$relationMethod->getLocalKey()};
            if (isset($keyedRelationData[$modelKey])) {
                $model->setRelation($relation, $keyedRelationData[$modelKey]);
            }
        }
    }

    /**
     * Create a new instance of the model being queried.
     *
     * @param  array  $attributes
     * @return \Mate\Database\Model|static
     */
    public function newModelInstance($attributes = [])
    {
        return $this->model->newInstance($attributes)->setConnection(
            $this->model->getConnection()

        );
    }

    /**
     * Create and return an un-saved model instance.
     *
     * @param  array  $attributes
     * @return \Mate\Database\Model|static
     */
    public function make(array $attributes = [])
    {
        return $this->newModelInstance($attributes);
    }

    /**
     * Save a new model and return the instance.
     *
     * @param  array  $attributes
     * @return \Mate\Database\Model|$this
     */
    public function create(array $attributes = [])
    {
        return tap($this->newModelInstance($attributes), function ($instance) {
            $instance->save();
        });
    }

    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the model instance being queried.
     *
     * @return \Mate\Database\Model|static
     */
    public function getModel()
    {
        return $this->model;
    }

    public function clone()
    {
        return clone $this;
    }

    public function cloneWithout(array $properties)
    {
        return tap($this->clone(), function ($clone) use ($properties) {
            foreach ($properties as $property) {
                $clone->{$property} = null;
            }
        });
    }

    /**
     * Destruct
     */
    public function __destruct()
    {
        unset($this->connection);
    }

    /**
     * Reset values
     */
    public function reset()
    {
        unset($this->conditions);
        unset($this->groupBy);
        unset($this->having);
        unset($this->orderBy);
        unset($this->innerJoin);
        unset($this->leftJoin);
        unset($this->rightJoin);
        unset($this->limit);
        unset($this->offset);
        unset($this->where);
        unset($this->selectCols);
        unset($this->fillableAttr);
        unset($this->insertKeys);
        unset($this->insertValues);
        unset($this->updateValues);
    }
}
