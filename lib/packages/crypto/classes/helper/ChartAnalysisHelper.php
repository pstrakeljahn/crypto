<?php

namespace PS\Packages\Crypto\Classes\Helper;

use Kensho\Chart\Chart\Chart;
use Kensho\Chart\Chart\ChartFactory;
use PS\Packages\Crypto\Classes\Crypto;
use PS\Packages\Crypto\Classes\CryptoDatapoint;

class ChartAnalysisHelper
{
    public static function analyseCrypto(Crypto $crypto, int $period)
    {
        $crypto->getID();
        $dataPoints = CryptoDatapoint::getInstance()
            ->add(CryptoDatapoint::CRYPTOID, $crypto->getID())
            ->orderBy(CryptoDatapoint::TIMESTAMP, 'ASC')
            ->select();

        $chart = self::getChartInstance($dataPoints);

        $SMAPeriod = 20;
        $EMAPeriod = 10;
        return json_decode(json_encode([
            "Period" => $period,
            "SMA" => $chart->getSMA($period),
            "EMA" => $chart->getEMA($period),
            "DI" => $chart->getDI($period),
            "ADX" => $chart->getADX($period),
            "SMAPeriod" => $SMAPeriod,
            "EMAPeriod" => $EMAPeriod,
            "Trend" => $chart->getTrend($SMAPeriod, $EMAPeriod),
        ]), true);
    }

    private static function getChartInstance(array $dataPoints): Chart
    {
        $chartData = [];
        foreach ($dataPoints as $dataPoint) {
            $chartData[date("Y-m-d H:i:s", $dataPoint->getTimestamp() / 1000)] = [
                'open'   => $dataPoint->getOpen(),
                'high'   => $dataPoint->getHigh(),
                'low'    => $dataPoint->getLow(),
                'close'  => $dataPoint->getClose(),
                'volume' => $dataPoint->getVolume()
            ];
        }
        $chart = ChartFactory::bootstrap($chartData);
        return $chart;
    }
}
