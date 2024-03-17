<?php

namespace PS\Packages\Crypto\Classes\Helper\ExternalApi\Handler;

use PS\Core\ExternalApiHelper\ApiConnector;

abstract class CryptoApi extends ApiConnector
{
    const BASEPATH = "https://api.crypto.com/exchange/%s/%s/%s";
    const VERSION = "v1";
    const SCOPE = 'public';

    public function setEndpoint($endpoint)
    {
        $this->setUrl(sprintf(self::BASEPATH, self::VERSION, self::SCOPE, $endpoint));
    }
}
