<?php

namespace PS\Core\Database;

use Exception;
use PS\Core\Logging\Logging;

class DatabaseHelper extends Criteria
{
    const IS_NOT_NULL = 'isNotNull';
    const IS_NULL = 'isNull';

    // private string $searchString;
    // private string $orderBy;
    // private string $limit;
    // private array $searchParams = array();

    public function getByPK(int $id)
    {
        $instanceName = self::getClassName(true);
        $table = $instanceName::TABLENAME;
        $db = new DBConnector();
        $db->query("SELECT * FROM `$table` WHERE ID =:id");
        $db->bind(':id', $id);
        $result =  $db->resultSet();
        if (!count($result)) {
            return null;
        }
        return $this->prepareResult($result)[0];
    }

    public function prepareResult($result): array
    {
        $output = [];
        if ($result) {
            foreach ($result as $row) {
                $instanceName = self::getClassName(true);
                $selfInstance = new $instanceName();
                foreach ($selfInstance as $key => &$value) {
                    // if (ctype_digit((string)$row[$key])) {
                    //     $row[$key] = (int)$row[$key];
                    // }
                    $value = $row[$key];
                }
                $output[] = $selfInstance;
            }
        }
        return $output;
    }

    public function add(string $column, $value, $criteria = null): self
    {
        switch ($criteria) {
            case null:
                $tmpQuery = 'WHERE ' . $column . ' = :' . $value . '';
                break;
            case Criteria::ISNOTNULL:
                $tmpQuery = 'WHERE ' . $column . ' IS NOT NULL';
                break;
            case Criteria::ISNULL:
                $tmpQuery = 'WHERE ' . $column . ' IS NULL';
                break;
            case Criteria::IN:
                if (!is_array($value)) {
                    throw new \Exception('Value has to be array if you want to use IN');
                }
                $tmpQuery = 'WHERE ' . $column . ' IN (' . implode(',', $value) . ')';
                break;
        }
        if (!is_array($value) && is_null($criteria)) {
            $this->searchParams[':' . $value] = $value;
        }

        if (!isset($this->searchString)) {
            $this->searchString = $tmpQuery;
        } else {
            $this->searchString = $this->searchString . ' AND ' . substr($tmpQuery, 6);
        }
        return $this;
    }

    public function orderBy(string $column, string $order)
    {
        if ($order === 'ASC' || $order === 'DESC') {
            $this->orderBy = 'ORDER BY ' . $column . ' ' . $order;
        }
        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function select(): array
    {
        $instanceName = self::getClassName(true);
        $table = $instanceName::TABLENAME;
        $query = 'SELECT * FROM ' . $table . ' ';
        if (isset($this->searchString)) {
            $query = $query  . $this->searchString;
        }
        if (isset($this->orderBy)) {
            $query = $query . ' ' . $this->orderBy;
        }
        if (isset($this->limit)) {
            $query = $query . ' LIMIT ' . $this->limit;
        }
        $db = new DBConnector();
        $db->query($query, isset($this->searchParams) ? $this->searchParams : null);
        unset($this->orderBy);
        unset($this->searchString);
        unset($this->limit);
        unset($this->searchParams);
        return $this->prepareResult($db->resultSet());
    }

    public function save()
    {
        if (method_exists($this, 'savePre')) {
            call_user_func_array([$this, 'savePre'], []);
        };
        if (isset($this->searchString)) {
            unset($this->searchString);
            $this->searchParams = array();
        }
        $this->checkValidity();

        // create a new entry
        $db = new DBConnector();
        try {
            $db->beginTransaction();
            if (is_null($this->getID())) {
                $instanceName = self::getClassName(true);
                $table = $instanceName::TABLENAME;
                $query = 'INSERT INTO ' . $table . ' (';
                $valueString = '';
                foreach ($this as $key => $value) {
                    if ($key === 'ID') {
                        continue;
                    }
                    $query = $query . $key . ', ';
                    if (is_null($value)) {
                        $valueString = $valueString . 'NULL, ';
                    } else {
                        $valueString = $valueString . '\'' . $value . '\'' . ', ';
                    }
                }
                $query = substr($query, 0, -2) . ') VALUES (' . substr($valueString, 0, -2) . ');';

                $db->query($query);
                $db->execute();

                $this->{'ID'} = (int)$db->lastInsertId();
            }
            // update entry
            if (!is_null($this->getID())) {
                $instanceName = self::getClassName(true);
                $table = $instanceName::TABLENAME;
                $query = 'UPDATE ' . $table . ' SET';
                $valueString = '';
                $condition = '';
                foreach ($this as $key => $value) {
                    if ($key === 'ID') {
                        $condition = ' WHERE ID = ' . $value . ';';
                        continue;
                    }
                    if (!is_null($value)) {
                        $value = '\'' . $value . '\'';
                    } else {
                        $value = 'NULL';
                    }
                    $query = $query . ' ' . $key . ' = ' . $value . ', ';
                }
                $query = substr($query, 0, -2) . $condition;
                $db->query($query);
                $db->execute();
            }
            $db->commit();
            return $this;
        } catch (Exception $e) {
            Logging::getInstance()->add(Logging::LOG_TYPE_ERROR, $e->getMessage());
            $db->rollback();
        }
    }

    public function delete(): bool
    {
        if (is_null($this->getID())) {
            return false;
        }
        $db = new DBConnector();
        $instanceName = self::getClassName(true);
        $table = $instanceName::TABLENAME;
        $query = 'DELETE FROM ' . $table . '  WHERE id=' . $this->getID() . ';';
        $db->query($query);
        $db->execute();
        return true;
    }

    public function getID()
    {
        return $this->{'ID'};
    }

    private function checkValidity(): void
    {
        try {
            foreach ($this as $key => $value) {
                $instanceName = self::getClassName(true);
                if (in_array($key, $instanceName::REQUIRED_VALUES) && is_null($this->{$key})) {
                    throw new Exception($key . ' is required!');
                }
            }
        } catch (Exception $e) {
            Logging::getInstance()->add(Logging::LOG_TYPE_DEBUG, $e->getMessage());
        }
    }

    protected static function getClassName(bool $getNamespace = false): string
    {
        if ($getNamespace) {
            return get_called_class();
        }
        $calledClass = explode('\\', get_called_class());
        return $calledClass[count($calledClass) - 1];
    }
}
