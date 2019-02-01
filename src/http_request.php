<?php

/**
 * Request to a http server
 * @author Lukas Buchs
 */
class http_request {
    protected $_userAgent = null;
    protected $_timeout = 60;
    protected $_url = '';
    protected $_referer = null;

    public function __construct($url, $referer=null) {
        $this->_userAgent = 'Mozilla/5.0 ('. PHP_OS .'; x64; rv:1.0) php/' . phpversion() . ' github.com/lbuchs/schiessanzeigen_ch/0.1';
        $this->_url = $url;
        $this->_referer = $referer;
    }

    public function exec() {
        $ch = curl_init();

        // setze die URL und andere Optionen
        curl_setopt($ch, CURLOPT_URL, $this->_url);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_userAgent);

        if ($this->_referer) {
            curl_setopt($ch, CURLOPT_REFERER, $this->_referer);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }
}
