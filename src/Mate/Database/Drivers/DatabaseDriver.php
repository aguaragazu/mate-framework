<?php

namespace Mate\Database\Drivers;

use Mate\Database\Database;

interface DatabaseDriver
{
    public function connect(
        string $protocol,
        string $host,
        int $port,
        string $database,
        string $username,
        string $password
    ): object;

    public function table(Database|string $table, ?string $as = null): Database;

    public function lastInsertId(): int;

    public function close();

    public function query(): Database;

    public function error(): string;

    public function errorCode(): int;

    public function errorMessage(): string;

    public function escape(string $string);

    public function affectedRows(): int;

    public function beginTransaction(): void ;

    public function commit(): void ;

    public function rollback(): void ;

    public function select($query, $bind = []): mixed;
    
    public function selectOne($query, $bind = []): mixed;
    
    public function insert($query, $bind = []): bool;

    public function update($query, $bind = []): int;

    public function delete($query, $bind = []): int;

    public function statement(string $query, array $bind = []): bool;

    public function affectingStatement(string $query, array $bind = []): mixed;
}
