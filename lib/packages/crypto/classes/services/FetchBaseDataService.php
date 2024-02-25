<?php

namespace PS\Packages\Crypto\Classes\Services;

use PS\Core\Service\ServiceHelper;
use PS\Packages\Crypto\Classes\Crypto;
use PS\Packages\Crypto\Classes\Helper\ExternalApi\Endpoint\GetInstruments;

class FetchBaseDataService extends ServiceHelper
{

    private $externalData = null;
    private $waitingSeconds = 86400;


    public function define()
    {
    }

    public function executeTick()
    {
        if ($this->getStartTime() - time() < $this->waitingSeconds && $this->getTick() > 0) {
            $this->addRow("New data will be fetched every 24h");
        } else {
            if (is_null($this->externalData)) {
                $this->addRow("Fetching external data...");
                $this->addBorder(false, true);
                $this->loadData();
            } else {
                $this->addRow(sprintf("Fetched %s datasets. Processing...", count($this->externalData)));
                $this->processData();
            }
        }
    }

    private function loadData()
    {
        $this->externalData = (new GetInstruments())->getResponse();
    }

    private function processData()
    {
        $data = array_shift($this->externalData);
        $arrCrypto = Crypto::getInstance()->add(Crypto::SYMBOL, $data['symbol'])->select();

        if (count($arrCrypto)) {
            $objCrypto = $arrCrypto[0];
        } else {
            $objCrypto = new Crypto;
            $objCrypto->setSymbol($data['symbol']);
        }

        $objCrypto
            ->setName($data['display_name'])
            ->setActive(0)
            ->setInstType($data['inst_type'])
            ->setBaseCcy($data['base_ccy'])
            ->setQuoteCcy($data['quote_ccy'])
            ->setQuoteDecimals($data['quote_decimals'])
            ->setQuantityDecimals($data['quantity_decimals'])
            ->setPriceTickSize($data['price_tick_size'])
            ->setQtyTickSize($data['qty_tick_size'])
            ->setMaxLeverage($data['max_leverage'])
            ->setTradable($data['tradable'])
            ->save();

        $this->addRow("");
        $this->addRow(sprintf("%s successfully saved", $data['display_name']));
    }
}
