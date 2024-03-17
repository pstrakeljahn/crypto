<?php

namespace PS\Core\ExternalApiHelper;

use Exception;

class ApiConnector
{
    private \CurlHandle $curlInstance;
    private ?string $url = null;
    private string $method = 'GET';
    protected ?string $contentType = null;
    protected array $header = [];
    protected array $requestParams = [];

    public function __construct()
    {
        $this->curlInstance = curl_init();

        curl_setopt_array($this->curlInstance, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
    }

    /**
     * It is necessary to specify a URL!
     * 
     * @param string $url URL as string
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Specify the method to be used. By default GET is selected
     * 
     * @param string $method ['GET', 'POST', 'PATCH', 'DELETE', 'OPTION']
     * @return self
     */
    public function setMethode(string $method): self
    {
        if (!in_array($method, ['GET', 'POST', 'PATCH', 'DELETE', 'OPTION'])) {
            throw new Exception('Unknown method');
        }
        $this->method;
        return $this;
    }

    /**
     * Specifies contentType
     * 
     * @param string $contentType
     * @return self
     */
    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * Set Header
     * 
     * @param array $header
     * @return self
     */
    public function setHeader(array $header): self
    {
        $this->$header;
        return $this;
    }

    /**
     * Adds parameters to be sent
     * 
     * @param array $arrField
     * @return self
     */
    public function setPostfields(array $arrField): self
    {
        curl_setopt($this->curlInstance, CURLOPT_POSTFIELDS, $arrField);
        return $this;
    }

    /**
     * Save Cookie to *.txt
     * 
     * @param string $cookiePath Path to a *.txt file  
     * @return self
     */
    public function setSaveCookie(string $cookiePath): self
    {
        if (file_exists($cookiePath)) {
            throw new Exception('There is a CookieFile (' . $cookiePath . ')');
        }
        curl_setopt($this->curlInstance, CURLOPT_HEADER, 1);
        curl_setopt($this->curlInstance, CURLOPT_COOKIEJAR, $cookiePath);
        return $this;
    }

    /**
     * Add Cookie
     * 
     * @param string $cookiePath Path to a *.txt file  
     * @return self
     */
    public function setCookie(string $cookiePath): self
    {
        if (file_get_contents($cookiePath) === false) {
            throw new Exception('Cookie file not found');
        }
        curl_setopt($this->curlInstance, CURLOPT_COOKIEFILE, $cookiePath);
        return $this;
    }

    /**
     * Returns the responde of the request
     * 
     * @return string|bool 
     */
    public function getPayload()
    {
        if (is_null($this->url)) {
            throw new Exception('Url has to be set.');
        }

        curl_setopt($this->curlInstance, CURLOPT_CUSTOMREQUEST, $this->method);

        $url = $this->url;
        if ($this->method === 'GET' && count($this->requestParams)) {
            $url .= "?";
            foreach ($this->requestParams as $property => $value) {
                $parmas[] = sprintf("%s=%s", $property, $value);
            }
            $url .= implode("&", $parmas);
        }

        curl_setopt($this->curlInstance, CURLOPT_URL, $url);

        $header = [];
        if (!is_null($this->contentType)) {
            $header[] = $this->contentType;
        }
        $header = [...$header, ...$this->header];
        if (count($header)) {
            curl_setopt($this->curlInstance, CURLOPT_HTTPHEADER, $header);
        }
        return curl_exec($this->curlInstance);
    }

    public function __destruct()
    {
        curl_close($this->curlInstance);
    }
}
