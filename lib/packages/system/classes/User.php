<?php

namespace PS\Packages\System\Classes;

use PS\Build\UserBasic;

/*
*   Logic can be implemented here that is not overwritten
*/

class User extends UserBasic
{
    public function select(): array
    {
        $result = parent::select();
        if (is_null($result)) return null;




        $output = [];
        if ($result) {
            foreach ($result as $row) {
                $selfInstance = new self;
                // $reflectionClass = new ReflectionClass($instanceName);
                // $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PRIVATE);
                // var_dump($instanceName);
                // die();
                foreach ($selfInstance as $key => &$value) {
                    // if (in_array($key, $properties)) continue;
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
}
