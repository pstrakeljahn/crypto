<?php

namespace PS\Core\Api;

use PS\Core\RequestHandler\Response;

class ApiHandler
{
    public static function run($path, $method)
    {
        $response = new Response();
        if (count($path) !== 3) {
            $response->generateResponse(null, ['code' => Response::STATUS_CODE_NOTFOUND, 'message' => 'Not found'], null, Response::STATUS_CODE_NOTFOUND);
        }

        // Get Endpoint
        $endpoint = $path[2];
        if (class_exists("PS\Source\Endpoints\\" . $endpoint)) {
            $call = strtolower($method);
            $class = "PS\Source\Endpoints\\" . $endpoint;
            $obj = new $class;
            $obj->define();
            if (!is_callable([$obj, $call])) {
                $response->generateResponse(null, ['code' => Response::STATUS_CODE_BAD_REQUEST, 'message' => 'Method not allowed'], null, Response::STATUS_CODE_BAD_REQUEST, false, $obj->getNeedsAuth());
                return;
            }
            if ($obj->getAcceptableParamaters() != array_keys($obj->requestParameter)) {
                echo json_encode($obj->badRequest());
                die();
            }
            $obj->$call();
            $response->generateResponse($obj->response, $obj->error, null, null, false, $obj->getNeedsAuth());
        } else {
            $response->generateResponse(null, ['code' => Response::STATUS_CODE_NOTFOUND, 'message' => 'Not found'], null, Response::STATUS_CODE_NOTFOUND, false);
        }
    }
}
