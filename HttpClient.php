<?php

class Testing_Core_HttpClient_Exception extends Exception
{
}

class Testing_Core_HttpClient
{
    const URL        = CURLOPT_URL;
    const USER_AGENT = CURLOPT_USERAGENT;
    const COOKIE     = CURLOPT_COOKIE;
    const METHOD_POST  = CURLOPT_POST;

    const RESPONSE_RAW    = 'response_raw';
    const RESPONSE_HEADER = 'response_header';
    const RESPONSE_BODY   = 'response_body';


    protected $host = null;
    protected $cookie = array();
    protected $session = array();

    protected $request = array();

    protected $requestHeader = '';
    protected $response = null;


    /**
     * Set host for requests
     *
     * @param string $host
     */
    public function setHost($host)
    {
        $param = parse_url($host, PHP_URL_HOST);
        $this->host = ($param)?$param:rtrim($host, '/');
    }


    /**
     * Set url for request
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->request[self::URL] = $this->getUrl($url);
    }


    /**
     * Open url using GET method
     *
     * @param $url
     */
    public function openUrl($url)
    {
        $this->setUrl($url);
        return $this->send();
    }


    /**
     * Set request parameter
     *
     * @param string $paramName
     * @param string $value
     */
    public function setParam($paramName, $value)
    {
        switch ($paramName) {
            case self::USER_AGENT:
                $this->session[self::USER_AGENT] = $value;
                break;

        }

        $this->request[$paramName] = $value;
    }


    /**
     * Get current request parameter
     *
     * @param $paramName
     * @return null
     */
    public function getParam($paramName)
    {
        return isset($this->request[$paramName])?
            $this->request[$paramName]:null;
    }


    /**
     * Set post parameter
     *
     * @param string $paramName
     * @param string $value
     */
    public function post($paramName, $value)
    {
        $this->setParam(self::METHOD_POST, 1);
        $post = (array)$this->getParam(CURLOPT_POSTFIELDS);
        $post[$paramName] = $value;
        $this->setParam(CURLOPT_POSTFIELDS, $post);
    }

    /**
     * Make full url
     *
     * @param $url
     * @return string
     */
    protected function getUrl($url)
    {
        if (!preg_match('/^http[s]?\:\/\//', $url)) {
            $url = 'http://' . $this->host . $url;
        }
        return $url;
    }


    /**
     * Send request
     *
     * @return string
     */
    public function send()
    {
        $this->response = null;
        $ch = curl_init();
        curl_setopt_array($ch, $this->request);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        $raw = curl_exec($ch);
        $info = curl_getinfo($ch);
        $this->requestHeader = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $this->parseResponse($raw, $info);
        $this->request = $this->session;
        $this->request[CURLOPT_REFERER] = $info['url'];
        return $this->getBody();
    }


    /**
     * Get response body
     *
     * @return null|string
     */
    public function getBody()
    {
        return $this->response[self::RESPONSE_BODY];
    }


    /**
     * Parse response
     *
     * @param $response
     * @param $info
     */
    protected function parseResponse($response, $info)
    {
        $header_size = $info['header_size'];
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        $this->response = array(
            self::RESPONSE_RAW => $response,
            self::RESPONSE_HEADER => $header,
            self::RESPONSE_BODY => $body
        );
        $this->parseHeader($header, $info);
    }


    /**
     * Parse header, you can using pecl parse_http_header
     *
     * @param string $header
     */
    protected function parseHeader($header)
    {
        $cookie = array_filter(
            explode("\n", $header),
            function($line) {
                return (strpos(strtolower($line), 'set-cookie:') === 0);
            }
        );
        $cookieStr = isset($this->session[self::COOKIE])?
            $this->session[self::COOKIE]:'';
        foreach ($cookie as $line) {
            $line = explode(':', $line, 2);
            $line = trim($line[1]);
            $line = explode(';', $line, 2);
            $cookieStr .= $line[0] . ';';
        }
        $this->session[self::COOKIE] = $cookieStr;
    }

}
