<?php

namespace PS\Core\Builder;

use PS\Core\Database\DBConnector;
use PS\Core\Logging\Logging;

class ClassBuilder extends DBConnector
{
    const KEYWORDS = ['CASCADE', 'SET NULL', 'NO ACTION', 'RESTRICT'];
    public $logInstance;
    public array $keyConstraints;
    public array $packageInfo;

    public function __construct()
    {
        $this->logInstance = Logging::getInstance();
        $this->keyConstraints = [];
    }

    public function buildClass(array $entity, string $filePathInfo, $validate, $onlyAdding, $force)
    {
        $this->packageInfo = include($filePathInfo);
        if ($validate) {
            if ($this->validateEntity($entity)) {
                $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Validation successful', false, true);
            } else {
                $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Validation failed - entity skipped', false, true);
                return;
            }
        }
        $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Table ' . $entity['tablename'] . ($this->generateTables(
            $entity,
            $onlyAdding,
            $force
        ) ? ' successfully ' : ' unsuccessfully ') . 'created', false, true);
        // @todo ALTERLOGIK FEHLT HIER NOCH!
        $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'BasicClass ' . ucfirst($entity['name']) . ($this->generateBasicClass(
            $entity,
            $this->packageInfo
        ) ? ' successfully ' : ' unsuccessfully ') . 'created', false, true);
    }

    private function validateEntity(array $entity): bool
    {
        if (!isset($entity['name'])) {
            $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Entity name is missing', false, true);
            return false;
        }
        if (!isset($entity['tablename'])) {
            $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Json is bad formated "tablename" is missing', false, true);
            return false;
        }
        foreach ($entity['defintion'] as $object) {
            if (!isset($object['name'])) {
                $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Json is bad formated "name" is missing', false, true);
                return false;
            }
            if (!$this->checkType($object)) return false;
        }
        return true;
    }

    private function checkType($object)
    {
        if (!isset($object['type'])) {
            $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Json is bad formated "type" is missing', false, true);
            return false;
        }

        $type = $object['type'];
        switch ($type) {
            case 'decimal':
                if (!isset($object['range'])) {
                    $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'If using "decimal" range is required', false, true);
                    return false;
                }
                break;
            case 'bool':
                break;
            case 'enum':
                if (!isset($object['values'])) {
                    $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'If using "enum" a Array of values has to be set', false, true);
                    return false;
                }
        }
        return true;
    }

    public function fetchKeyConstraints()
    {
        foreach ($this->keyConstraints as $query) {
            $db = new DBConnector();
            try {
                $db->query($query);
                $db->execute();
            } catch (\Exception $e) {
            }
        }
    }

    private function generateTables(array $entity, bool $onlyAdding, bool $force): bool
    {
        $tablename = $entity['tablename'];
        $query = "CREATE TABLE IF NOT EXISTS `" . $entity['tablename'] . "` (";

        // HIER KANN NOCH NE KONDITION REIN. ERSTMAL IMMER MIT ID
        if (true) {
            $query = $query . "`ID` int(11) unsigned NOT NULL auto_increment,";
            // $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Column ID added', false, true);
        }
        foreach ($entity['defintion'] as $entity) {
            if ($entity['type'] === 'enum') {
                $enumValues = '';
                foreach ($entity['values'] as $value) {
                    $enumValues = $enumValues . '\'' . $value . '\',';
                }
                $enumValues = trim($enumValues, ",");
                $query = $query . "`" . $entity['name'] . "` ENUM (" . $enumValues . ") ";
            } elseif ($entity['type'] === 'datetime') {
                $query = $query . "`" . $entity['name'] . "` " . $entity['type'] . " ";
            } elseif ($entity['type'] === 'date') {
                $query = $query . "`" . $entity['name'] . "` " . $entity['type'] . " ";
            } elseif ($entity['type'] === 'bool') {
                $query = $query . "`" . $entity['name'] . "` boolean ";
            } elseif ($entity['type'] === 'decimal') {
                $query = $query . "`" . $entity['name'] . "` " . $entity['type'] . "(" . $entity['range'] . ") ";
            } else {
                $query = $query . "`" . $entity['name'] . "` " . $entity['type'] . "(" . $entity['length'] . ") ";
            }
            // $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Column ' . $entity['name'] . ' added', false, true);
            if (isset($entity['notnull']) && $entity['notnull']) {
                $query = $query . "NOT NULL";
            }
            if (isset($entity['unique']) && $entity['unique']) {
                $query = $query . " UNIQUE";
            }
            if ($entity['type'] === 'bool' && isset($entity['default'])) {
                if ($entity['default'] === true || $entity['default'] === false) {
                    $default = $entity['default'] ? '1' : '0';
                    $query = $query . "DEFAULT " . $default;
                }
            }
            if (isset($entity['reference']) && $entity['ref_column'] && isset($entity['ref_update']) && isset($entity['ref_delete'])) {
                $onUpdate = strtoupper($entity['ref_update']);
                $onDelete = strtoupper($entity['ref_delete']);
                if (in_array($onUpdate, self::KEYWORDS) && in_array($onDelete, self::KEYWORDS)) {
                    $this->keyConstraints[] = 'ALTER TABLE `' . $tablename . '` CHANGE `' . $entity['name'] . '` `' . $entity['name'] . '` INT(11) UNSIGNED';
                    $this->keyConstraints[] = 'ALTER TABLE `' . $tablename . '` ADD CONSTRAINT `FK_' . $entity['reference'] . $entity['name'] . count($this->keyConstraints) . '` FOREIGN KEY (`' . $entity['name'] . '`) REFERENCES `' . $entity['ref_table'] . '`(`' . $entity['ref_column'] . '`) ON DELETE ' . $onDelete . ' ON UPDATE ' . $onUpdate;
                }
            }

            $query = $query . ",";
        }
        $query = $query . "PRIMARY KEY  (`ID`)) ENGINE = InnoDB;";
        $db = new DBConnector();
        $db->query($query);
        return $db->execute();
    }

    private function generateBasicClass(array $entity, array $packageInfo): bool
    {
        try {
            $initClass = new BuildClassFile($entity, $packageInfo);
            return $initClass->execute();
        } catch (\Exception $e) {
            Logging::getInstance()->add(Logging::LOG_TYPE_ERROR, $e->getMessage());
        }
    }
}
