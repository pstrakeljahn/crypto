<?php

namespace PS\Core\Builder;

use Config;
use PS\Core\Logging\Logging;

class BuildClassFile
{

    private $entity;
    private $className;
    private $fileName;
    private $templatePath;
    private $fileContent;
    private $fileContentGetterSetter;
    private array $virtualCheck;
    private array $requiredValues;
    private array $packageInfo;

    public function __construct(array $entity, array $packageInfo)
    {
        $this->packageInfo = $packageInfo;
        $this->entity = $entity;
        $this->className = ucfirst($entity['name']);
        $this->fileName = Config::BASE_PATH . 'lib/build/' . $this->className . 'Basic.php';
        $this->templatePath = Config::BASE_PATH . 'lib/core/builder/templates/';
        $this->fileContent = file_get_contents($this->templatePath . 'basicClassTemplate.txt');
        $this->fileContentGetterSetter = file_get_contents($this->templatePath . 'getterSetterTemplate.txt');
    }

    public function execute(): bool
    {
        $this->fileContent = str_replace('###seperator###', "explode('\\\', __CLASS__);", $this->fileContent);
        $this->fileContent = str_replace('###className###', $this->className, $this->fileContent);
        $this->fileContent = str_replace('###definitionOfAttr###', $this->prepareProperties(), $this->fileContent);
        $this->fileContent = str_replace('###namespace###', "PS\Packages\\" . ucfirst($this->packageInfo['packageName']) . "\Classes\\" . ucfirst($this->className), $this->fileContent);
        $this->prepareRequiredValues();
        if (!is_dir(Config::BASE_PATH . 'lib/build/')) {
            mkdir(Config::BASE_PATH . 'lib/build/', 777);
        }
        if (file_put_contents($this->fileName, $this->fileContent) !== false && $this->prepareSetterGetter() && $this->prepareClass($this->virtualCheck)) {
            return true;
        }
        return false;
    }

    private function prepareProperties()
    {
        $returnString = '    const ID = \'ID\';' . PHP_EOL;;
        foreach ($this->entity['defintion'] as $column) {
            if (isset($column['virtual']) && $column['virtual']) {
                continue;
            }
            $returnString = $returnString . '    const ' . strtoupper($column['name']) . ' = \'' . $column['name'] . '\';' . PHP_EOL;
            if ($column['type'] === 'enum') {
                foreach ($column['values'] as $value) {
                    $returnString = $returnString . '    const ENUM_' . strtoupper($column['name']) . '_' . strtoupper($value) . ' = \'' . $value . '\';' . PHP_EOL;
                }
            }
        }
        return $returnString  . PHP_EOL . '    const TABLENAME = \'' . $this->entity['tablename'] . '\';' . PHP_EOL;
    }

    private function prepareSetterGetter(): bool
    {
        // ID IS HARDCODED!
        $concatString = '';
        foreach ($this->entity['defintion'] as $column) {
            $this->virtualCheck = [];
            if (isset($column['virtual']) && $column['virtual']) {
                continue;
            }
            $concatString = $concatString . $this->fileContentGetterSetter;
            $concatString = str_replace('###value###', $column['name'], $concatString);
            $concatString = str_replace('###VALUE###', ucfirst($column['name']), $concatString);
        }
        if (file_put_contents($this->fileName, $concatString, FILE_APPEND | LOCK_EX) === false) {
            return false;
        }
        if (file_put_contents($this->fileName, '}', FILE_APPEND | LOCK_EX) === false) {
            return false;
        }
        return true;
    }

    private function prepareClass(): bool
    {
        $fileName = Config::BASE_PATH . 'lib/packages/' . $this->packageInfo['packageName'] . '/classes/' . $this->className . '.php';
        if (file_exists($fileName)) {
            foreach ($this->entity['defintion'] as $column) {
                if (isset($column['virtual']) && $column['virtual']) {
                    $namespace = "PS\Packages\\" . ucfirst($this->packageInfo['packageName']) . "\Classes\\" . ucfirst($this->className);
                    if (!method_exists(new $namespace(), 'get' . ucfirst($column['name']))) {
                        Logging::getInstance()->add(Logging::LOG_TYPE_DEBUG, $namespace . '::get' . ucfirst($column['name']) . '() is not callable');
                    }
                    if (!method_exists(new $namespace(), 'set' . ucfirst($column['name']))) {
                        Logging::getInstance()->add(Logging::LOG_TYPE_DEBUG, $namespace . '::set' . ucfirst($column['name']) . '() is not callable');
                    }
                }
            }
            return true;
        }
        $fileContent = file_get_contents($this->templatePath . 'classTemplate.txt');
        $fileContent = str_replace('###className###', $this->className, $fileContent);
        $fileContent = str_replace('###namespace###', "PS\Packages\\" . ucfirst($this->packageInfo['packageName']) . "\Classes", $fileContent);
        if (file_put_contents($fileName, $fileContent) === false) {
            return false;
        }

        //check virtual values
        return true;
    }

    private function prepareRequiredValues(): void
    {
        $this->requiredValues = [];
        foreach ($this->entity['defintion'] as $entity) {
            if (isset($entity['required']) && $entity['required']) {
                array_push($this->requiredValues, $entity['name']);
            }
        }
        $requiredValuesString = '\'' . implode("', '", $this->requiredValues) . '\'';
        if ($requiredValuesString === "''") {
            $requiredValuesString = substr($requiredValuesString, 0, -2);
        }
        $this->fileContent = str_replace('###requiredValues###', $requiredValuesString, $this->fileContent);
    }
}
