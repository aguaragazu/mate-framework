<?php

namespace Mate\Database\Drivers;

use mysqli;
use Mate\Database\Exception\DatabaseException;

/**
 * MySQL database driver
 */
class MysqliDriver implements DatabaseDriver
{
    protected ?mysqli $mysqli;
    protected $table;
    protected $result;
    protected $affectedRows;

    public function connect(
        string $protocol,
        string $host,
        int    $port,
        string $database,
        string $username,
        string $password
    ): object {
        if ($protocol != 'mysql') {
            throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_PROTOCOL);
        }

        $this->mysqli = new mysqli($host, $username, $password, $database, $port);
        if ($this->mysqli->connect_errno) {
            throw new DatabaseException(DatabaseException::ERR_MSG_CONNECTION_FAIL);
        }
        return $this;
    }

    public function statement(string $query, array $bind = []): mixed
    {
        $stm = $this->mysqli->execute_query($query, $bind)
        ->fetch_all(MYSQLI_ASSOC);
        if (!$stm) {
            throw new DatabaseException($this->mysqli->error);
        }

        $this->result = $stm;
        $this->affectedRows = $$this->mysqli->affected_rows;

        return [
             'result' => $this->result,
            'affectedRows' => $this->affectedRows
        ];
    }

    /**
     * Execute MySQL query
     * @param  string $query
     * @return void
     */
    public function query(string $query)
    {
        $stm = $this->mysqli->query($query);
        if (!$stm) {
            throw new DatabaseException($this->mysqli->error);
        }

        $this->result = $stm;
        $this->affectedRows = $$this->mysqli->affected_rows;

        return [
             'result' => $this->result,
            'affectedRows' => $this->affectedRows
        ];
    }

    /**
     * MySQL escapse
     * @param  mixed $sql
     * @return mixed
     */
    public function escape($sql)
    {
        return $this->mysqli->real_escape_string($sql);
    }

    /**
     * Return MySQL request to Array
     * @param  mysql_result $result
     * @return array
     */
    public function resultToArray($result)
    {
        $arr = [];
        while ($row = $result->fetch_assoc()) {
            $arr[] = $row;
        }

        return $arr;
    }

    /**
     * MySQL error
     * @return string
     */
    public function error(): string
    {
        return $this->mysqli->error;
    }

    public function errorCode(): int
    {
        return $this->mysqli->errno;
    }

    public function errorMessage(): string
    {
        return $this->mysqli->error;
    }

    public function close()
    {
        return $this->mysqli->close();
    }

    /**
     * MySQL Last Insert Id
     * @return int
     */
    public function lastInsertId(): int
    {
        return (int) $this->mysqli->insert_id;
    }

    public function affectedRows(): int
    {
        return (int) $this->mysqli->affected_rows;
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction(): void
    {
        $this->mysqli->begin_transaction();
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit(): void
    {
        $this->mysqli->commit();
    }

    /**
     * Roll back current transaction
     * @return bool
     */
    public function rollBack(): void
    {
        $this->mysqli->rollback();
    }
}
