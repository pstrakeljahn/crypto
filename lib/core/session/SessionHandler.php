<?php

namespace PS\Core\Session;

use PS\Core\RequestHandler\Request;
use PS\Packages\System\Classes\User;

class SessionHandler extends Request
{
    public static function loggedIn(): bool
    {
        if (!is_null(self::getUserID())) {
            return true;
        }
        return false;
    }

    private static function getUserID(): ?int
    {
        $token = self::getBearerToken();
        if (is_null($token)) {
            return null;
        }
        $payload = TokenHelper::decodeToken($token);
        if (is_null($payload)) {
            return null;
        }
        $userID = $payload['userID'];
        if (is_numeric($userID)) {
            return (int)$userID;
        }
    }

    public static function whoami(): ?User
    {
        $userID = self::getUserID();
        if (is_null($userID)) {
            return null;
        }
        $user = User::getInstance()->add(User::ID, $userID)->select();
        if (count($user)) {
            return $user[0];
        }
        return null;
    }

    private static function getAuthorizationHeader(): ?string
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    private static function getBearerToken(): ?string
    {
        $headers = self::getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
