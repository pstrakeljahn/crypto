<?php

namespace PS\Packages\Crypto\Classes\Helper\ExternalApi\Endpoint;

use PS\Packages\Crypto\Classes\Helper\ExternalApi\Handler\CryptoApi;

class GetInstruments extends CryptoApi
{
    public function __construct()
    {
        $this->setEndpoint('get-instruments');
    }

    public function getResponse()
    {
        $data = $this->execute();
        return $data['result']['data'];
    }
}
