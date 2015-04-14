<?php

namespace Wicked\Git;

/**
 * Class HttpClient
 *
 * @desc HTTP client to send/recieve JSON over HTTP requests
 * @package Wicked\Git
 */
/**
 * Class HttpClient
 *
 * @package Wicked\Git
 */
class HttpClient
{
    /**
     * @var string
     */
    private $cacheDir;
    /**
     * @var string|null
     */
    private $token;

    /**
     * @var array
     */
    private $requestHeaders = array();
    /**
     * @var string
     */
    private $responseCode;
    /**
     * @var array
     */
    private $responseHeaders = array();
    /**
     * @var string
     */
    private $responseBody;

    /**
     * @param string|null $token
     */
    function __construct($token = null)
    {
        $this->cacheDir = sys_get_temp_dir();
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->responseCode;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getHeader($name)
    {
        return isset($this->responseHeaders[$name]) ? $this->getHeaders()[$name] : null;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->responseBody;
    }

    /**
     * @param string $url
     * @param string $method
     * @param string|null   $body
     *
     * @return mixed|null
     * @throws Exception
     */
    public function send($url, $method = 'GET', $body = null)
    {
        $this->requestHeaders = array();

        if ($this->token) {
            if (strpos($this->token, ':') !== false) {
                $this->requestHeaders['Authorization'] = 'Basic '.base64_encode(trim($this->token));
            } else {
                $this->requestHeaders['Authorization'] = 'Token '.trim($this->token);
            }
        }

        $this->responseCode = 500;
        $this->responseHeaders = array();
        $this->responseBody = null;
        
        $options = array(
            'http' => array(
                'ignore_errors' => true,
                'method' => $method,
                'header' => ""
            )
        );
        $this->requestHeaders['User-Agent'] = 'peej/git';
        if ($body) {
            $options['http']['content'] = json_encode($body);
            $this->requestHeaders['Content-Type'] = 'application/json';
            $cacheFilename = $this->cacheDir.'/peej-git-'.md5($url.$method.$options['http']['content']);
        } else {
            $cacheFilename = $this->cacheDir.'/peej-git-'.md5($url.$method);
        }

        if ($method == 'GET' && file_exists($cacheFilename)) {
            $cache = json_decode(file_get_contents($cacheFilename));
            $this->requestHeaders['If-None-Match'] = $cache->headers->ETag;
            $this->responseBody = $cache->body;
        }

        foreach ($this->requestHeaders as $name => $value) {
            $options['http']['header'] .= $name.': '.$value."\n";
        }
        
        $context = stream_context_create($options);
        $stream = fopen($url, 'r', false, $context);

        foreach (stream_get_meta_data($stream)['wrapper_data'] as $header) {
            $parts = explode(':', $header);
            if (!isset($parts[1])) {
                $this->responseCode = (int)substr($parts[0], 9, 3);
            } else {
                $this->responseHeaders[trim($parts[0])] = trim($parts[1]);
            }
        }

        if ($this->responseCode != 304 || $this->responseBody == null) {
        
            $this->responseBody = json_decode(stream_get_contents($stream));

            if ($this->responseCode >= 400) {
                throw new Exception($url.' returned error '.$this->responseCode.' ('.(isset($this->responseBody->message) ? $this->responseBody->message : '').')', $this->responseCode);
            }

            if (isset($this->responseHeaders['ETag'])) { // write cache file
                file_put_contents($cacheFilename, json_encode(array(
                    'headers' => $this->responseHeaders,
                    'body' => $this->responseBody
                ), JSON_PRETTY_PRINT));
            }

            fclose($stream);
        }

        if ($method == 'PATCH') {
            sleep(10); // wait to let GitHub catch up, not sure if this is a bug, seems to help for now
        }

        return $this->responseBody;
    }

}