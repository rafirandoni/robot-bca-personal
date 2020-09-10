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
    protected $cookieDir;
    protected $cookieName;
    protected $debug = true;

    protected $ipAddress;

    public function setConfiguration(array $configuration = [])
    {
        if (isset($configuration['baseUrl'])) {
            $this->setBaseUrl($configuration['baseUrl']);
        }

        if (isset($configuration['timeout'])) {
            $this->setTimeout($configuration['timeout']);
        }

        if (isset($configuration['cookie_dir'])) {
            $this->setCookieDir($configuration['cookie_dir']);
        }

        if (isset($configuration['cookie_name'])) {
            $this->setCookieName($configuration['cookie_name']);
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

    public function setCookieDir(string $cookieDir)
    {
        $this->cookieDir = rtrim($cookieDir, '/').DIRECTORY_SEPARATOR;
    }

    public function setCookieName(string $cookieName)
    {
        $this->cookieName = $cookieName;
    }

    /**
     * Get cookie instance
     *
     * @return \GuzzleHttp\Cookie\FileCookieJar
     */
    public function getCookieJar()
    {
        if (! $this->cookieJar) {
            $cookieFile = $this->cookieDir.$this->cookieName;
            if (! file_exists($cookieFile)) {
                @touch($cookieFile);
            }
            $this->cookieJar = new FileCookieJar($cookieFile, true);
        }

        return $this->cookieJar;
    }

    /**
     * Get curl instance
     *
     * @return \GuzzleHttp\Client;
     */
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

    /**
     * Create new request
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return object
     */
    public function request(string $method, string $uri, array $options = [])
    {
        if ($this->debug) {
            $debugUri = ltrim($uri, '/');
            if (isset($options['query'])) {
                $debugUri .= '?'.http_build_query($options['query']);
            }
            $debugUri = str_replace('/', '-', $debugUri);

            if (strlen($debugUri) < 1) {
                $debugUri = mt_rand(10000, 99999);
            }

            $logPath = $this->cookieDir.date('ymd-').$debugUri.'.html';
        }

        try {
            $curlRequest = $this->getCurl()->request($method, $uri, $options);
        } catch (\Exception $e) {
            if ($this->debug) {
                $this->storeLog($logPath, $e->getMessage());
            }

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

        if ($this->debug) {
            $this->storeLog($logPath, $response);
        }

        return (object)[
            'success' => true,
            'data' => $response,
        ];
    }

    public function storeLog(string $filepath = '', ?string $log = null)
    {
        if (substr($filepath, -1) == '/') {
            $filepath = rtrim($filepath, '/').'landing';
        }

        $f = fopen($filepath, 'w+');
        fwrite($f, $log);
        fclose($f);
    }

    public function getIpAddress()
    {
        if (! $this->ipAddress) {
            try {
                $client = new Client([
                    'base_uri' => 'https://www.cloudflare.com',
                    'verify' => false,
                    'timeout' => 3
                ]);
                $curlRequest = $client->request('GET', '/cdn-cgi/trace', []);
                $response = $curlRequest->getBody()->getContents();

                $ipAddress = null;
                foreach (explode("\n", $response) as $res) {
                    if (strpos($res, 'ip=') !== false) {
                        $ipAddress = str_replace('ip=', '', $res);
                        break;
                    }
                }

                if ($ipAddress) {
                    $this->ipAddress = $ipAddress;
                }
            } catch (\Exception $e) {
            }
        }

        return $this->ipAddress;
    }
}