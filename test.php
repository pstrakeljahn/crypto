<?php

use PS\Packages\Crypto\Classes\Helper\ExternalApi\Endpoint\GetCandleStick;
use PS\Packages\Crypto\Classes\Helper\ExternalApi\Endpoint\GetInstruments;
use PS\Packages\Crypto\Classes\Services\FetchBaseDataService;

require_once __DIR__ . '/autoload.php';

class Test
{
    public function run()
    {
        $t = (new GetCandleStick)->setInstrumentName('BTCUSD-PERP')->getResponse();
        $f = 2;
        // $serviceInstance = new FetchBaseDataService;
        // $serviceInstance->start();
    }
}

(new Test)->run();
