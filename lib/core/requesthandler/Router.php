<?php

namespace PS\Core\RequestHandler;

use Config;
use PS\Core\Database\Criteria;
use PS\Core\Session\TokenHelper;
use PS\Packages\System\Classes\User;

class Router extends Request
{
    private $path;
    private $method;
    private $input;

    const CREATE = 'create';
    const READ = 'read';
    const UPDATE = 'update';
    const DELETE = 'delete';

    const CRUD_OPERATIONS_METHOD = [
        'POST',
        'GET',
        'PATCH',
        'DELETE'
    ];

    public function __construct()
    {
        $this->path = $_SERVER['REDIRECT_URL'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->input = "";
        if (substr($this->path, -1) !== "/") {
            $this->path = $this->path . '/';
        }
        if (file_get_contents('php://input') !== "") {
            $data = array();
            parse_str(file_get_contents('php://input'), $data);
            $this->input = $data;
        }
    }

    public function run($match)
    {
        try {

            // check obj endpoint
            $arrUrl = explode('/', $match[count($match) - 1]);
            // Explode request to strip params
            if (empty($arrUrl[0])) {
                $error = ['code' => Response::STATUS_CODE_NOTFOUND, 'message' => 'No Object selected!'];
                call_user_func_array([$this, $this->method], [[], $_GET, $_POST, $this->input, $error]);
                return;
            }
            $classIndex = require(Config::BASE_PATH . 'lib/build/_index.php');
            $className = isset($classIndex[ucfirst(explode("?", $arrUrl[0])[0])]) ? $classIndex[ucfirst(explode("?", $arrUrl[0])[0])] : "";
            $error = ['code' => null, 'message' => null];
            if (!class_exists($className)) {
                $error = ['code' => Response::STATUS_CODE_NOTFOUND, 'message' => 'Object' . $className . ' does not exist!'];
                call_user_func_array([$this, $this->method], [[], $_GET, $_POST, $this->input, $error]);
                return;
            } else if (empty($arrUrl[1]) && $this->method === 'GET') {
                // GET all without ID
                $instance = new $className();
                $res = self::searchObject($instance, $_GET);
                call_user_func_array([$this, $this->method], [$res, $_GET, $_POST, $this->input, $error]);
                return;
            } else if (empty($arrUrl[1]) && $this->method === 'POST') {
                if (!count($_POST) && empty($input)) {
                    $error = ['code' => Response::STATUS_CODE_BAD_REQUEST, 'message' => 'Request body is empty'];
                    $this->generateResponse(null, $error, "post");
                    return;
                }
                call_user_func_array([$this, $this->method], [new $className(), $_GET, $_POST, $this->input, $error]);
                return;
            };
            if (isset($arrUrl[1]) && is_numeric($arrUrl[1]) && in_array($this->method, ['GET', 'PATCH', 'DELETE'])) {
                $objInstance = new $className();
                $obj = $objInstance->getByPK((int)$arrUrl[1]);
                if (is_null($obj)) {
                    $obj = new $className();
                    $obj->ID = (int)$arrUrl[1];
                    $error = ['code' => Response::STATUS_CODE_NOTFOUND, 'message' => 'Object with ID ' . (int)$arrUrl[1] . ' was not found'];
                }
                // single object selected
                call_user_func_array([$this, $this->method], [$obj, $_GET, $_POST, $this->input, $error, (int)$arrUrl[1]]);
                return;
            } else if (isset($arrUrl[1]) && !is_numeric($arrUrl[1]) && in_array($this->method, self::CRUD_OPERATIONS_METHOD)) {
                if ($arrUrl[1] !== "") {
                    $error = ['code' => Response::STATUS_CODE_BAD_REQUEST, 'message' => 'ID has to be an int!'];
                } else {
                    $error = ['code' => Response::STATUS_CODE_BAD_REQUEST, 'message' => 'Wrong usage'];
                }
                call_user_func_array([$this, $this->method], [null, $_GET, $_POST, $this->input, $error, (int)$arrUrl[1]]);
                return;
            } else {
                $error = ['code' => Response::STATUS_CODE_BAD_REQUEST, 'message' => 'Wrong usage'];
                call_user_func_array([$this, $this->method], [null, $_GET, $_POST, $this->input, $error]);
                return;
            };
        } catch (\Exception $e) {
            $error = ['code' => Response::STATUS_SERVER_ERROR, 'message' => $e->getMessage()];
            $this->generateResponse(null, $error, $this->method);
            return;
        }
    }

    public function login()
    {
        $error = ['code' => null, 'message' => null];
        if (isset($this->input['username']) && isset($this->input['password'])) {
            $user = User::getInstance()->add(User::USERNAME, $this->input['username'])->select();
            $error['code'] = self::STATUS_CODE_BAD_REQUEST;
            $error['message'] = 'Login failed';
            $obj = ['login', array()];
            if (count($user)) {
                if (password_verify($this->input['password'], $user[0]->getPassword())) {
                    $token = TokenHelper::createToken($user[0]);
                    $error['code'] = self::STATUS_CODE_OK;
                    $error['message'] = null;
                    $obj = ['login', array('token' => $token)];
                }
            }
        } else {
            $error['code'] = self::STATUS_CODE_BAD_REQUEST;
            $error['message'] = 'Username and password must be sent!';
            $obj = ['login', array()];
        }
        call_user_func_array([$this, $this->method], [$obj, $_GET, $_POST, $this->input, $error]);
    }

    private static function searchObject($instance, $params)
    {
        foreach ($params as $key => $value) {
            if (method_exists($instance, 'get' . ucfirst($key))) {
                if ($value === "null" || $value === "NULL") {
                    $instance->add($key, $value, Criteria::ISNULL);
                } else {
                    $instance->add($key, $value);
                }
            }
        }
        return $instance->select();
    }
}
