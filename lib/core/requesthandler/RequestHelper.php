<?php

namespace PS\Core\RequestHandler;

class RequestHelper
{
    public static function insertDataIntoObject($obj, array $requestData, bool $isNew = false)
    {
        $error = null;
        $missingParams = [];
        if ($isNew) {
            $missingParams = array_diff($obj::REQUIRED_VALUES, array_keys($requestData));
        }
        if (count($missingParams)) {
            $obj = null;
            $error = ['code' => Response::STATUS_CODE_NOT_MODIFIED, 'message' => 'Missing parameters: ' . implode($missingParams)];
        } else {
            foreach ($requestData as $key => $data) {
                if ($key === 'ID') {
                    continue;
                }
                if (method_exists($obj, 'set' . ucfirst($key))) {
                    $obj = call_user_func_array([$obj, 'set' . ucfirst($key)], [$data]);
                }
            }
            $obj->save();
            $obj = $obj->getByPk($obj->getID());
        }
        if (!is_null($error)) {
            throw new \Exception(json_encode($error));
        }
        return $obj;
    }
}
