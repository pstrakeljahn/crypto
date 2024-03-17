<?php

namespace PS\Packages\Crypto\Classes\Helper\ExternalApi\Endpoint;

use PS\Packages\Crypto\Classes\Helper\ExternalApi\Handler\CryptoApi;

class GetInstruments extends CryptoApi
{
    public function __construct()
    {
        $this->setEndpoint('get-instruments');
        $this->setMethode('GET');
        $this->setContentType('ContentType: application/json');
        parent::__construct();
    }

    public function getResponse()
    {
        $data = json_decode($this->getPayload(), true);
        return $data['result']['data'];
    }
}
