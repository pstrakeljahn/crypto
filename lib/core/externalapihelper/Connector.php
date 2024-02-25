<?php

namespace PS\Core\ExternalApiHelper;

use Exception;

class Connector
{
    private $curlInstance;
    private $urlIsSet = false;

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
        curl_setopt($this->curlInstance, CURLOPT_URL, $url);
        $this->urlIsSet = true;
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
        if (in_array($method, ['GET', 'POST', 'PATCH', 'DELETE', 'OPTION'])) {
            throw new Exception('Unknown method');
        }
        curl_setopt($this->curlInstance, CURLOPT_CUSTOMREQUEST, $method);
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
    public function getResponse()
    {
        if (!$this->urlIsSet) {
            throw new Exception('Url has to be set.');
        }
        return curl_exec($this->curlInstance);
    }

    public function __destruct()
    {
        curl_close($this->curlInstance);
    }
}
