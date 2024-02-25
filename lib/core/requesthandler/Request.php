<?php

namespace PS\Core\RequestHandler;


class Request extends Response
{
    public function get($obj, $get, $post, $input, $error = null, $id = null)
    {
        $loginRequest = false;
        if (gettype($obj) === "array" && isset($obj[0]) && $obj[0] === "login") {
            $loginRequest = true;
        }
        $this->generateResponse($obj, $error, __FUNCTION__, null, $loginRequest);
    }

    public function options($obj, $get, $post, $input, $error = null, $id = null)
    {
        $this->generateResponse($obj, $error, __FUNCTION__);
    }

    protected function post($obj, $get, $post, $input, $error = null, $id = null)
    {
        $requestData = $post;
        if (!empty($input)) {
            $requestData = is_array($input) ? $input : json_decode($input, true);
        }

        $loginRequest = false;
        if (gettype($obj) === "array" && $obj[0] === "login") {
            $loginRequest = true;
        }
        if (!is_null($obj) && !$loginRequest) {
            try {
                $obj = RequestHelper::insertDataIntoObject($obj, $requestData ?? array(), true);
            } catch (\Exception $e) {
                $error = ['code' => Response::STATUS_SERVER_ERROR, 'message' => $e->getMessage()];
            }
        }
        $this->generateResponse($loginRequest ? $obj[1] : $obj, $error, __FUNCTION__, null, $loginRequest);
    }

    protected function patch($obj, $get, $post, $input, $error = null, $id = null)
    {
        $requestData = $post;
        if (!empty($input)) {
            $requestData = json_decode($input, true);
        }
        if (!is_null($obj)) {
            try {
                $obj = RequestHelper::insertDataIntoObject($obj, $requestData);
            } catch (\Exception $e) {
                $error = ['code' => Response::STATUS_SERVER_ERROR, 'message' => $e->getMessage()];
            }
        }
        $this->generateResponse($obj, $error, __FUNCTION__);
    }

    protected function delete($obj, $get, $post, $input, $error = null, $id = null)
    {
        if (!is_null($obj)) {
            $obj->delete();
        }
        $this->generateResponse(null, $error, __FUNCTION__);
    }
}
