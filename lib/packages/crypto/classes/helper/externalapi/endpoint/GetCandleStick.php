<?php

namespace PS\Packages\Crypto\Classes\Helper\ExternalApi\Endpoint;

use Exception;
use PS\Packages\Crypto\Classes\Helper\ExternalApi\Handler\CryptoApi;

class GetCandleStick extends CryptoApi
{
    const PERIOD_ONE_MINUTE = "1m";
    const PERIOD_FIVE_MINUTES = "5m";
    const PERIOD_FIFTEEN_MINUTES = "15m";
    const PERIOD_THIRTY_MINUTES = "30m";
    const PERIOD_ONE_HOUR = "1h";
    const PERIOD_FOUR_HOURS = "2h";
    const PERIOD_SIX_HOURS = "4h";
    const PERIOD_TWELVE_HOURS = "12h";
    const PERIOD_ONE_DAY = "1D";
    const PERIOD_ONE_WEEK = "7D";
    const PERIOD_TWO_WEEKS = "14D";
    const PERIOD_ONE_MONTH = "1M";

    const ARRAY_PERIOD = [
        self::PERIOD_ONE_MINUTE => 60,
        self::PERIOD_FIVE_MINUTES => 300,
        self::PERIOD_FIFTEEN_MINUTES => 900,
        self::PERIOD_THIRTY_MINUTES => 1800,
        self::PERIOD_ONE_HOUR => 3600,
        self::PERIOD_FOUR_HOURS => 7200,
        self::PERIOD_SIX_HOURS => 14400,
        self::PERIOD_TWELVE_HOURS => 43200,
        self::PERIOD_ONE_DAY => 86400,
        self::PERIOD_ONE_WEEK => 604800,
        self::PERIOD_TWO_WEEKS => 1209600,
        self::PERIOD_ONE_MONTH => 2592000,
    ];

    public function __construct()
    {
        $this->setEndpoint('get-candlestick');
    }

    public function setInstrumentName(string $val): self
    {
        $this->requestParams['instrument_name'] = $val;
        return $this;
    }

    public function setTimeframe(string $val): self
    {
        $this->requestParams['timeframe'] = $val;
        return $this;
    }

    public function setCount(int $val): self
    {
        $this->requestParams['count'] = $val;
        return $this;
    }

    public function setStartTimestamp(int $val): self
    {
        $this->requestParams['start_ts'] = $val;
        return $this;
    }

    public function setEndTmestamp(int $val): self
    {
        $this->requestParams['end_ts'] = $val;
        return $this;
    }

    public function getResponse()
    {
        $data = $this->execute();
        if (isset($data['message'])) throw new Exception("Instrumentname unknown");
        return $data['result']['data'];
    }
}
