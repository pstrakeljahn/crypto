<?php

namespace PS\Packages\Crypto\Classes\Services;

use PS\Core\Service\ServiceHelper;
use PS\Packages\Crypto\Classes\Crypto;
use PS\Packages\Crypto\Classes\CryptoDatapoint;
use PS\Packages\Crypto\Classes\Helper\ExternalApi\Endpoint\GetCandleStick;

class FetchCandleStickDataService extends ServiceHelper
{

    const TIMEFRAME_RECHECK_ACTIVE = 10;

    private array $activeCryptos = [];
    private int $updateCount = 0;


    public function define()
    {
    }

    public function executeTick()
    {
        if (time() - $this->getStartTime() > $this->updateCount * self::TIMEFRAME_RECHECK_ACTIVE && !count($this->activeCryptos)) {
            $this->getActiveCurrencies();
            $this->addRow("Active Cryptos loaded.");
            if (!count($this->activeCryptos)) {
                $this->addRow("No active cryptos.");
            }
        } else {
            if (count($this->activeCryptos)) {
                $crypto = array_shift($this->activeCryptos);
                $response = $this->callApi($crypto);
                $this->addRow("API called for " . $crypto->getName());
                $this->saveData($crypto, $response);
                $this->addRow("-> saved");
            } else {
                $this->addRow("Waiting...");
            }
        }
    }

    private function getActiveCurrencies()
    {
        $this->activeCryptos = Crypto::getInstance()->add(Crypto::ACTIVE, 1)->select();
        $this->updateCount++;
    }

    private function callApi(Crypto $crypto)
    {
        $lastDatapoint = CryptoDatapoint::getInstance()
            ->add(CryptoDatapoint::CRYPTOID, $crypto->getID())
            ->orderBy(CryptoDatapoint::TIMESTAMP, 'DESC')
            ->limit(1)
            ->select();
        return (new GetCandleStick)
            ->setInstrumentName($crypto->getName())
            ->setCount(100)
            ->setTimeframe($this->calcPeriod($lastDatapoint))
            ->getResponse();
    }

    private function calcPeriod(array $datapoint)
    {
        if (!count($datapoint)) return GetCandleStick::PERIOD_ONE_MONTH;
        $lastSync = $datapoint[0]->getTimestamp();
        $arrIntervals = GetCandleStick::ARRAY_PERIOD;
        asort($arrIntervals);
        foreach ($arrIntervals as $key => $duration) {
            if ($lastSync <= $duration) {
                return $key;
            }
        }
        return GetCandleStick::PERIOD_ONE_MONTH;
    }

    private function saveData(Crypto $crypto, array $data)
    {
        // @todo Improve it! Otherwise database might be killed by this in the furture!
        foreach ($data as $entry) {
            $dbResult = CryptoDatapoint::getInstance()->add(CryptoDatapoint::ID, $crypto->getID())->add(CryptoDatapoint::TIMESTAMP, $entry['t'])->select();
            if (count($dbResult)) {
                $dataPoint = $dbResult[0];
            } else {
                $dataPoint = (new CryptoDatapoint)->setCryptoID($crypto->getID())->setTimestamp($entry['t']);
            }
            $dataPoint
                ->setOpen($entry['o'])
                ->setHigh($entry['h'])
                ->setLow($entry['l'])
                ->setClose($entry['c'])
                ->setVolume($entry['v'])
                ->save();
        }
    }
}