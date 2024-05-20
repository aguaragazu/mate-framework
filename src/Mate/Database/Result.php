<?php

namespace Mate\Database;

class Result
{
    private $statement;
    private $numRows;
    private $rows;

    public function __construct(\PDOStatement $statement)
    {
        $this->statement = $statement;
        $this->numRows = $statement->rowCount();
        $this->rows = [];

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $this->rows[] = $row;
        }
    }

    public function getNumRows(): int
    {
        return $this->numRows;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function fetchRow(): array
    {
        return $this->statement->fetch(\PDO::FETCH_ASSOC);
    }

    public function fetchColumn(int $columnIndex): array
    {
        $columnValues = [];

        while ($row = $this->statement->fetchColumn($columnIndex)) {
            $columnValues[] = $row;
        }

        return $columnValues;
    }

    public function fetchSingleColumn(int $columnIndex): mixed
    {
        $row = $this->statement->fetchColumn($columnIndex);
        return $row === false ? null : $row;
    }

    public function close(): void
    {
        $this->statement->closeCursor();
    }
}