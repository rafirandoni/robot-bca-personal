<?php

namespace BcaPersonal;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;

trait BaseRequest
{
    protected $cookieJar;
    protected $curl;
    protected $baseUrl;
    protected $timeout = 60;    // in second
    protected $cookiePath;

    public function setConfiguration(array $configuration = [])
    {
        if (isset($configuration['baseUrl'])) {
            $this->setBaseUrl($configuration['baseUrl']);
        }

        if (isset($configuration['timeout'])) {
            $this->setTimeout($configuration['timeout']);
        }

        if (isset($configuration['cookie_path'])) {
            $this->setCookiePath($configuration['cookie_path']);
        }
    }

    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function setCookiePath(string $cookiePath)
    {
        $this->cookiePath = $cookiePath;
    }

    public function getCookieJar()
    {
        if (! $this->cookieJar) {
            $cookieFile = $this->cookiePath;
            if (! file_exists($cookieFile)) {
                @touch($cookieFile);
            }
            $this->cookieJar = new FileCookieJar($cookieFile, true);
        }

        return $this->cookieJar;
    }

    public function getCurl()
    {
        if (! $this->curl) {
            $this->curl = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => $this->timeout,
                'verify' => false,
                'curl' => [
                    CURLOPT_FRESH_CONNECT => 1,
                    CURLOPT_FOLLOWLOCATION => 1,
                ],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.83 Safari/537.36'
                ],
                'cookie' => true,

            ]);
        }

        return $this->curl;
    }

    public function request(string $method, string $uri, array $options = [])
    {
        try {
            $curlRequest = $this->getCurl()->request($method, $uri, $options);
        } catch (\Exception $e) {
            return (object)[
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        $response = $curlRequest->getBody()->getContents();
        $temp = json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            $response = $temp;
            unset($temp);
        }

        return (object)[
            'success' => true,
            'data' => $response,
        ];
    }
}