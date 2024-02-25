<?php

namespace PS\Core\Api;

use PS\Core\RequestHandler\Response;

class ApiExtender
{
    public array $acceptableParamaters;
    public array $allowsMethodes;
    public array $requestParameter;
    public mixed $response;
    public array $error;
    private bool $needsAuth = true;

    public function __construct()
    {
        $this->acceptableParamaters = array();
        $this->allowsMethodes = array();
        $this->requestParameter = array();
        // $this->setHeaders();
        if (file_get_contents('php://input') !== "") {
            parse_str(file_get_contents('php://input'), $requestParameter);
            $this->requestParameter = $requestParameter;
        }
    }

    protected function setAllowedMethodes($allowsMethodes)
    {
        $this->allowsMethodes = $allowsMethodes;
        return $this;
    }

    public function getAllowsMethodes(): array
    {
        return $this->allowsMethodes;
    }

    protected function setNeedsAuth(bool $needsAuth)
    {
        $this->needsAuth = $needsAuth;
        return $this;
    }

    public function getNeedsAuth(): bool
    {
        return $this->needsAuth;
    }

    protected function setAcceptableParamaters($acceptableParamaters)
    {
        $this->acceptableParamaters = $acceptableParamaters;
        return $this;
    }

    public function getAcceptableParamaters(): array
    {
        return $this->acceptableParamaters;
    }

    protected function setResponse($response, $error = null, $status = null)
    {
        $this->response = $response;
        $this->error = ['code' => !is_null($status) ? $status : Response::STATUS_CODE_BAD_REQUEST, 'message' => $error];
    }

    private function setHeaders(): void
    {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
            header('Access-Control-Max-Age: 86400');
            header('Content-Length: 0');
            header('Content-Type: text/plain');
            http_response_code(200);
        } else {
            // You can add additional header for endpoints
            header('Content-Type: application/json, charset=utf-8');
            header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
            header("Access-Control-Allow-Origin: *");
            header('Access-Control-Allow-Method: POST, GET, OPTIONS, PUT');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, x-xsrf-token, x_csrftoken, Cache-Control, X-Requested-With, pragma, Accept');
        }
    }

    public function badRequest($noInput = false)
    {
        http_response_code(400);
        return [
            'status' => 400,
            'data' => null,
            'error' => $noInput ? 'No Parameters sent' : 'Parameters are not valid'
        ];
    }
}
