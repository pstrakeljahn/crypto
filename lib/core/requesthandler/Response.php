<?php

namespace PS\Core\RequestHandler;

use Config;
use PS\Core\Logging\Logging;
use PS\Core\Session\SessionHandler;

class Response
{
    const BODY = [
        'status' => null,
        'data' => null,
        'error' => null
    ];

    const FALLBACK_ERROR_MESSAGES = [
        304 => 'NOT MODIFIED',
        400 => 'BAD REQUEST',
        403 => 'FORBIDDEN',
        404 => 'NOT FOUND',
        500 => 'SERVER ERROR'
    ];

    const STATUS_CODE_OK = 200;
    const STATUS_CODE_NOT_MODIFIED = 304;
    const STATUS_CODE_BAD_REQUEST = 400;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_NOTFOUND = 404;
    const STATUS_SERVER_ERROR = 500;

    public static function generateResponse($obj, $error, $methode = null, $statusCode = null, $loginRequest = false, bool $needsAuth = true)
    {
        if ($methode === "post" || $methode === "patch") {
            // Does nothing
        } elseif ($methode === "get") {
            if ($loginRequest) {
                self::generateResponse(null, ['code' => Response::STATUS_CODE_BAD_REQUEST, 'message' => 'Method not allowed'], null, Response::STATUS_CODE_BAD_REQUEST);
            }
            $obj = self::checkApiReadable($obj);
        }
        header('Content-Type: application/json, charset=utf-8');
        header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Method: POST, GET, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, x-xsrf-token, x_csrftoken, Cache-Control, X-Requested-With, pragma, Expires');

        if ($methode === 'options') {
            echo json_encode(array('data' => 'no support, only preflight'));
            exit(0);
        }
        http_response_code($statusCode ?? (isset($error['code']) ? $error['code'] : self::STATUS_CODE_OK));
        header('Status: ' . ($statusCode ?? (isset($error['code']) ? $error['code'] : self::STATUS_CODE_OK)));
        $response = self::BODY;
        $response['status'] = $statusCode ?? (isset($error['code']) ? $error['code'] : self::STATUS_CODE_OK);
        $response['data'] = ($response['status'] === Response::STATUS_SERVER_ERROR || $response['status'] === Response::STATUS_CODE_NOTFOUND) ? null : $obj;
        $response['error'] = (isset($error['code']) && !is_null($error['code'])) ?
            (isset($error['message']) && is_null($error['message']) ? self::FALLBACK_ERROR_MESSAGES[$error['code']] : $error['message'])
            : null;
        $user = is_null($obj) ? null : SessionHandler::whoami();
        if (!$loginRequest && $needsAuth) {
            if (is_null($user)) {
                if (!is_null($obj)) {
                    Logging::getInstance()->add(Logging::LOG_TYPE_ERROR, 'User does not exist');
                    $response = self::BODY;
                    $response['status'] = self::STATUS_SERVER_ERROR;
                    $response['error'] = 'User does not exist';
                }
            } else {
                Logging::getInstance()->add(
                    Logging::LOG_TYPE_API,
                    'User ID: '
                        . $user->getID() . ', Object: "'
                        . ((is_null($obj) || gettype($obj) === "array") ? null : (get_class(gettype($obj) !== "array" ? $obj : $obj[0] ?? "-")))
                        . '" method: "'
                        . strtoupper($methode)
                        . '", ID: '
                        . ((is_null($obj) || gettype($obj) === "array") ? null : ((gettype($obj) === "array") ? (count($obj) ? 'all' : "-") : $obj->getID() ?? 'null'))
                        . ', Code: ' . $response['status']
                );
            }
        }
        if (isset($error['code']) && !is_null($error['code']) && isset($error['message']) && !is_null($error['message'])) {
            Logging::getInstance()->add(Logging::LOG_TYPE_ERROR, $error['message']);
        }

        echo json_encode($response);
        die();
    }

    public static function checkApiReadable($obj)
    {
        if (is_null($obj)) {
            return null;
        }
        if (gettype($obj) === "array") {
            foreach ($obj as &$object) {
                $object = self::doFiltering($object);
            }
        } else {
            $obj = self::doFiltering($obj);
        }
        return $obj;
    }

    private static function doFiltering($obj)
    {
        // ID IS HARDCODED!
        $arrPath = explode("\\", get_class($obj));
        $entityPath = Config::BASE_PATH . 'lib/packages/' . strtolower($arrPath[2]) . '/database/' . $arrPath[4] . '.json';
        $entity = json_decode(file_get_contents($entityPath), true)['defintion'];
        foreach ($entity as $column) {
            if (isset($column['apiReadable']) && !$column['apiReadable']) {
                unset($obj->{$column['name']});
            }
        }
        return $obj;
    }
}
