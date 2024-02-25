<?php

namespace PS\Packages\Crypto\Classes\Helper\ExternalApi\Handler;

use Exception;

abstract class CryptoApi
{

    private string $basePath = "https://api.crypto.com/exchange/%s/%s/%s";
    private string $version = "v1";
    protected string $scope = 'public';
    protected string $methode = 'GET';
    protected array $additonalHeader = [];
    private ?string $requestUrl = null;

    public function setEndpoint($endpoint)
    {
        $this->requestUrl = sprintf($this->basePath, $this->version, $this->scope, $endpoint);
    }

    public function execute()
    {
        if (is_null($this->requestUrl)) {
            throw new Exception('Request Url has to be set!');
        }
        try {
            $response = $this->curlRequest();
        } catch (Exception $e) {
            throw $e;
        }
        return json_decode($response, true);
    }

    private function curlRequest()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'ContentType: application/json',
                ...$this->additonalHeader
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
