<?php

namespace PS\Packages\Crypto\Classes\Helper\ExternalApi\Endpoint;

use PS\Packages\Crypto\Classes\Helper\ExternalApi\Handler\CryptoApi;

class GetCandleStick extends CryptoApi
{
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
        return $data['result']['data'];
    }
}
