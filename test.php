<?php

use PS\Packages\Crypto\Classes\Crypto;
use PS\Packages\Crypto\Classes\Helper\ChartAnalysisHelper;

require_once __DIR__ . '/autoload.php';

class Test
{
    public function run()
    {
        $data = ChartAnalysisHelper::analyseCrypto((new Crypto)->getByPK(133), 2);
        file_put_contents("debug.json", json_encode($data, JSON_PRETTY_PRINT));
        $sma = end($data['SMA']);
        $ema = end($data['EMA']);
        $di = end($data['DI']);
        $adx = end($data['ADX']);
        $trend = end($data['Trend']);
        if ($sma > $ema && $adx > $di && $trend['close'] > $sma) {
            echo "Starke Kaufempfehlung";
        } elseif ($sma < $ema && $adx > $di && $trend['close'] < $sma) {
            echo "Starke Verkaufsempfehlung";
        } else {
            echo "Neutral";
        }
    }
}

(new Test)->run();
