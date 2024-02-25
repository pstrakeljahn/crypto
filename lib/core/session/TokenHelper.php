<?php

namespace PS\Core\Session;

use Config;
use PS\Core\RequestHandler\Request;
use PS\Packages\System\Classes\User;

class TokenHelper extends Request
{
    public static function createToken(User $user): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode(
            [
                'userID' => $user->getID(),
                'username' => $user->getUsername(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getSurname(),
                'mail' => $user->getMail(),
                'exp' => is_null(Config::EXPIRATION) ? null : (time() + Config::EXPIRATION)
            ]
        );

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, Config::SECRET, true);
        $base64UrlSignature = self::base64url_encode($signature);
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        return $jwt;
    }

    public static function decodeToken($jwt, $secret = Config::SECRET): ?array
    {
        $tokenParts = explode('.', $jwt);
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signature_provided = $tokenParts[2];

        $expiration = json_decode($payload)->exp;
        $is_token_expired = is_null($expiration) ? true : ($expiration - time()) < 0;

        $base64_url_header = self::base64url_encode($header);
        $base64_url_payload = self::base64url_encode($payload);
        $signature = hash_hmac('SHA256', $base64_url_header . "." . $base64_url_payload, $secret, true);
        $base64_url_signature = self::base64url_encode($signature);

        $is_signature_valid = ($base64_url_signature === $signature_provided);
        $payload_encode = json_decode($payload, true);

        if ($is_signature_valid && (!$is_token_expired || is_null(Config::EXPIRATION))) {
            return $payload_encode;
        } else {
            return null;
        }
    }

    public static function base64url_encode($str)
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }
}
