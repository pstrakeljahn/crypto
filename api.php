<?php

use PS\Core\Api\ApiHandler;
use PS\Core\RequestHandler\Response;
use PS\Core\RequestHandler\Router;
use PS\Core\Session\SessionHandler;

require_once __DIR__ . '/autoload.php';

$uri = $_SERVER['REQUEST_URI'];

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
    header('Access-Control-Max-Age: 86400');
    header('Content-Length: 0');
    header('Content-Type: text/plain');
}

preg_match('/^.*(api\/v1\/obj\/)(.*)$/', $uri, $match);
preg_match('/^.*(api\/v1\/mod\/)(.*)$/', $uri, $mod);
preg_match('/.*(cronjob.php)/', $uri, $cronjob);
preg_match('/.*(services.php)/', $uri, $services);
preg_match('/^.*(api\/v1\/login)(.*)$/', $uri, $login);
preg_match('/.*(build.php)/', $uri, $build);
preg_match('/.*(test.php)/', $uri, $test);

if (count($login)) {
    $router = new Router();
    $router->login();
    return;
}
if (count($match)) {
    if (SessionHandler::loggedIn() || $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        $router = new Router();
        $router->run($match);
    } else {
        Response::generateResponse(null, ['code' => Response::STATUS_CODE_FORBIDDEN, 'message' => 'Please lock in!']);
    }
} elseif (count($cronjob)) {
    return include('./cronjob.php');
} elseif (count($mod)) {
    ApiHandler::run($mod, $_SERVER['REQUEST_METHOD']);
    return;
} elseif (count($build)) {
    return include('./build.php');
} elseif (count($test)) {
    return include('./test.php');
} elseif (count($services)) {
    return include('./services.php');
} else {
    return;
    // @todo Has to be reworked
    $arrFullUri = explode("/", $uri);
    $arrUri = array_splice($arrFullUri, 2);
    $requestedRoute = implode("/", $arrUri);

    $arrAvailabeRoutes  = include('./page/routes/routes.php');
    if (isset($arrAvailabeRoutes[$requestedRoute])) {
        return include($arrAvailabeRoutes[$requestedRoute]);
    } else {
        return include('./page/index.php');
    }
}
