<?php

namespace Mate\Database\Drivers;

use Closure;
use DateTimeInterface;
use Exception;
use Mate\Database\Database;
use PDO;
use Mate\Database\Exception\DatabaseException;
use Mate\Database\Exception\Exception as MateException;
use Mate\Database\Exception\LostConnectionException;
use Mate\Database\Exception\QueryException;
use Mate\Database\ParameterType;
use Mate\Database\Result;
use Mate\Database\Traits\DetectsLostConnections;
use Mate\Support\Arr;
use Mate\Support\InteractsWithTime;
use Mate\Traits\Macroable;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * PDO connnection
 */
class PdoDriver implements DatabaseDriver
{
    use DetectsLostConnections, 
        InteractsWithTime,
        Macroable;

    protected ?PDO $pdo;
    protected string $database;
    protected string $table;
    protected $result;
    protected int $affectedRows = 0;
    protected bool $recordsModified = false;
    /**
     * The default fetch mode of the connection.
     *
     * @var int
     */
    protected $fetchMode = PDO::FETCH_OBJ;

    /**
     * The number of active transactions.
     *
     * @var int
     */
    protected $transactions = 0;
    /**
     * The database connection configuration options.
     *
     * @var array
     */
    protected $config = [];
    /**
     * The reconnector instance for the connection.
     *
     * @var callable
     */
    protected $reconnector;
    /**
     * All of the queries run against the connection.
     *
     * @var array
     */
    protected $queryLog = [];

    /**
     * Indicates whether queries are being logged.
     *
     * @var bool
     */
    protected $loggingQueries = false;

    /**
     * The duration of all executed queries in milliseconds.
     *
     * @var float
     */
    protected $totalQueryDuration = 0.0;

    /**
     * return in instance of the PDO object that connects to the SQLite database
     * @return \PDO
     */
    public function connect(
        string $protocol,
        string $host,
        int    $port,
        string $database,
        string $username,
        string $password
    ): object {
        match ($protocol) {
            "mysql" => $dsn = "mysql:host=$host;port=$port;dbname=$database",
            "sqlite" => $dsn = "sqlite:$database",
            "pgsql" => $dsn = "pgsql:host=$host;port=$port;dbname=$database",
            "oci" => $dsn = "oci:dbname=$database",
            "sqlsrv" => $dsn = "sqlsrv:server=$host;Database=$database",
            default => throw new \Exception("Unknown protocol $protocol")
        };

        if ($username && $password) {
            $dsn .= ";username=$username;password=$password";
        }
        // $dsn .= ";charset=utf8mb4";
        $this->database = $database;
        $this->pdo = new PDO($dsn);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this;
    }

    /**
     * Get the name of the connected database.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->database;
    }

    /**
     * Set the name of the connected database.
     *
     * @param  string  $database
     * @return $this
     */
    public function setDatabaseName($database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Set the reconnect instance on the connection.
     *
     * @param  callable  $reconnector
     * @return $this
     */
    public function setReconnector(callable $reconnector)
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    /**
     * Get the server version for the connection.
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function exec(string $query): int
    {
        try{
            $result = $this->pdo->exec($query);

            \assert($result !== false);

            return $result;
        } catch (MateException $e) {
            echo $e->getMessage();
        }
    }

    public function prepare(string $sql) 
    {
        return $this->createStatement(
            $this->pdo->prepare($sql)
        );
    }

    public function createStatement(PDOStatement $stmt): mixed
    {
        return $stmt;
    }

    /**
     * close the connection to the SQLite database
     */
    public function close()
    {
        $this->pdo = null;
    }

    /**
     * return the last insert id
     * @return int
     */
    public function lastInsertId(): int
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * return the result of the last query
     * @param string $query
     * @param array $bind
     * @return bool
     */
    public function statement(string $statement, array $bind = []): bool
    {
        if (substr_count($statement, "?") != count($bind)) {
            throw new \BadMethodCallException("The number of '?' marks in string '$statement' must match the number of bind values for " . json_encode($bind));
        }

        $statement = $this->pdo->prepare($statement);

        $this->bindValues($statement, $this->prepareBindings($bind));

        $this->recordsHaveBeenModified();

        return $statement->execute();
    }

    // /**
    //  * return the result of the last query
    //  * @param string $query
    //  * @return array
    //  */
    // public function query(string $query): mixed
    // {
    //     try {
    //         $stmt = $this->pdo->query($query);
    //         \assert($stmt instanceof PDOStatement);
    //         $this->table = $stmt->getColumnMeta(0)["table"] ?? null;
    //         $res = new Result($stmt);
    
    //         $this->affectedRows = $res->getNumRows();
    //         $this->result = $res;
    
    //         return $this->result;
    //     } catch (MateException $e){
    //         echo $e->getMessage();
    //     } 
    // }

    /**
     * Get a new query Database instance.
     *
     * @return \Mate\Database\Database
     */
    public function query(): Database
    {
        return new Database($this);
    }

    public function prepareBindings(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            // We need to transform all instances of DateTimeInterface into the actual
            // date string. Each query grammar maintains its own date string format
            // so we'll just ask the grammar for the format to get from the date.
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_bool($value)) {
                $bindings[$key] = (int) $value;
            }
        }

        return $bindings;
    }

    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_resource($value) => PDO::PARAM_LOB,
                    default => PDO::PARAM_STR
                },
            );
        }
    }

    /**
     * MySQL error
     * @return string
     */
    public function error(): string
    {
        return $this->pdo->errorinfo()[2] ?? 'Error al ejecutar la consulta';
    }

    /**
     * MySQL error code
     * @return int
     */
    public function errorCode(): int
    {
        return $this->pdo->errorInfo()[1];
    }

    /**
     * MySQL error message
     * @return string
     */
    public function errorMessage(): string
    {
        return $this->pdo->errorInfo()[2];
    }

    /**
     * MySQL affected rows
     * @return int
     */
    public function affectingStatement(string $query = null, array $bind = []): int
    {
        $statement = $this->pdo->prepare($query);
        $this->bindValues($statement, $this->prepareBindings($bind));
        $statement->execute($bind);

        $this->recordsHaveBeenModified(
            ($this->affectedRows = $statement->rowCount()) > 0
        );

        if ($statement === false) {
            throw new DatabaseException($this->pdo->errorInfo()[2]);
        }
        $this->result = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->affectedRows;
    }

    /**
     * Configure the PDO prepared statement.
     *
     * @param  \PDOStatement  $statement
     * @return \PDOStatement
     */
    protected function prepared(PDOStatement $statement): PDOStatement
    {
        $statement->setFetchMode($this->fetchMode);

        return $statement;
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string  $query
     * @return bool
     */
    public function unprepared(string $query): bool
    {
        $this->recordsHaveBeenModified(
            $change = $this->pdo->exec($query) !== false
        );

        return $change;
    }

    /**
     * Indicate if any records have been modified.
     *
     * @param  bool  $value
     * @return void
     */
    public function recordsHaveBeenModified($value = true)
    {
        if (!$this->recordsModified) {
            $this->recordsModified = $value;
        }
    }

    public function affectedRows(): int
    {
        return $this->affectedRows;
    }


    /**
     * MySQL start transaction
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * MySQL commit transaction
     * @return void
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * MySQL rollback transaction
     * @return void
     */
    public function rollback(): void
    {
        $this->pdo->rollback();
    }

    /**
     * Escape a value for safe SQL embedding.
     *
     * @param  string|float|int|bool|null  $value
     * @param  bool  $binary
     * @return string
     */
    public function escape($value, $binary = false)
    {
        if ($value === null) {
            return 'null';
        } elseif ($binary) {
            return $this->escapeBinary($value);
        } elseif (is_int($value) || is_float($value)) {
            return (string) $value;
        } elseif (is_bool($value)) {
            return $this->escapeBool($value);
        } elseif (is_array($value)) {
            throw new RuntimeException('The database connection does not support escaping arrays.');
        } else {
            if (str_contains($value, "\00")) {
                throw new RuntimeException('Strings with null bytes cannot be escaped. Use the binary escape option.');
            }

            if (preg_match('//u', $value) === false) {
                throw new RuntimeException('Strings with invalid UTF-8 byte sequences cannot be escaped.');
            }

            return $this->escapeString($value);
        }
    }

    /**
     * Wrap quotes around the given input.
     *
     * @param  string  $input
     * @param  string  $type
     * @return string
     */
    public function quote($input, $type = ParameterType::STR)
    {
        return $this->pdo->quote($input, $type);
    }

    /**
     * MySQL escape string
     * @param string $string
     * @return string
     */
    public function escapeString(string $string)
    {
        return $this->pdo->quote($string);
    }

    /**
     * Escape a boolean value for safe SQL embedding.
     *
     * @param  bool  $value
     * @return string
     */
    protected function escapeBool($value)
    {
        return $value ? '1' : '0';
    }

    /**
     * Escape a binary value for safe SQL embedding.
     *
     * @param  string  $value
     * @return string
     */
    protected function escapeBinary($value)
    {
        throw new RuntimeException('The database connection does not support escaping binary values.');
    }

    public function resultToArray($result)
    {
        $array = [];
        foreach ($result as $row) {
            $array[] = $row;
        }
        $this->result = $array;
        return $this->result;
    }

    public function table(Database|string $table, ?string $as = null): Database
    {
        return $this->query()->from($table, $as);
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function insert($query, $bind = []): bool
    {
        return $this->statement($query, $bind);
    }

    /**
     * Run an update statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function update($query, $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function delete($query, $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    public function selectOne($query, $bindings = []): mixed
    {
        $records = $this->select($query, $bindings);

        return array_shift($records);
    }

    public function select($query, $bindings = []): mixed
    {   
        $statement = $this->prepared(
            $this->pdo->prepare($query)
        );

        $this->bindValues($statement, $this->prepareBindings($bindings));

        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Reconnect to the database.
     *
     * @return mixed|false
     *
     * @throws \Mate\Database\LostConnectionException
     */
    public function reconnect()
    {
        if (is_callable($this->reconnector)) {

            return call_user_func($this->reconnector, $this);
        }

        throw new LostConnectionException('Lost connection and no reconnector available.');
    }
    
    /**
     * Reconnect to the database if a PDO connection is missing.
     *
     * @return void
     */
    public function reconnectIfMissingConnection()
    {
        if (is_null($this->pdo)) {
            $this->reconnect();
        }
    }

    /**
     * Disconnect from the underlying PDO connection.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->setPdo(null)->setReadPdo(null);
    }

    /**
     * Set the PDO connection.
     *
     * @param  \PDO|\Closure|null  $pdo
     * @return $this
     */
    public function setPdo($pdo)
    {
        $this->transactions = 0;

        $this->pdo = $pdo;

        return $this;
    }

    

    public function run ($query, $bind, $callback)
    {
        $start = microtime(true);

        try {
            $result = $this->runQueryCallback($query, $bind, $callback);
        } catch (QueryException $e) {
            $result = $this->handleQueryException(
               $e, $query, $bind, $callback
            );
        }

        $this->logQuery(
            $query, $bind, $this->getElapseTime($start)  
        );

        return $result;
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int  $start
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Run a SQL statement.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Mate\Database\Exception\QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            return $callback($query, $bindings);
        }

        // If an exception occurs when attempting to run a query, we'll format the error
        // message to include the bindings with SQL, which will make this exception a
        // lot more helpful to the developer instead of just the database's errors.
        catch (Exception $e) {
            throw new QueryException(
                $this->getName(), $query, $this->prepareBindings($bindings), $e
            );
        }
    }

    /**
     * Get the database connection name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getConfig('name');
    }

    /**
     * Get an option from the configuration options.
     *
     * @param  string|null  $option
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return Arr::get($this->config, $option);
    }

    /**
     * Handle a query exception.
     *
     * @param  \Mate\Database\Exception\QueryException  $e
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Mate\Database\Exception\QueryException
     */
    protected function handleQueryException(QueryException $e, $query, $bindings, Closure $callback)
    {
        if ($this->transactions >= 1) {
            throw $e;
        }

        return $this->tryAgainIfCausedByLostConnection(
            $e, $query, $bindings, $callback
        );
    }

    /**
     * Handle a query exception that occurred during query execution.
     *
     * @param  \Mate\Database\Exception\QueryException  $e
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Mate\Database\Exception\QueryException
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
    {
        if ($this->causedByLostConnection($e->getPrevious())) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  float|null  $time
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        $this->totalQueryDuration += $time ?? 0.0;

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time');
        }
    }
}
