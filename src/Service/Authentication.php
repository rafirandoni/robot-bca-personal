<?php

namespace BcaPersonal\Service;

use BcaPersonal\Scraper;

class Authentication
{
    protected $scraper;

    public function __construct(Scraper $scraper)
    {
        $this->scraper = $scraper;
    }

    /**
     * Authenticate user with given credentials
     *
     * @param array $credentials
     * @return void
     *
     * Desc :
     * - credentials :
     *   - username
     *   - password
     *   - user_ip
     */
    public function login($credentials = [])
    {
        $fetchLanding = $this->fetchLanding();
        if (! $fetchLanding->success) {
            return $fetchLanding;
        }

        $landingData = $fetchLanding->data;

        $credentials['ipAddress'] = $landingData['ipAddress'];
        $requestLogin = $this->requestLogin($credentials);
        if (! $requestLogin->success) {
            return $requestLogin;
        }

        $fetchWelcomePage = $this->fetchWelcomePage();
        if (! $fetchWelcomePage->success) {
            return $fetchWelcomePage;
        }

        $fetchWelcomePageSelectTransaction = $this->fetchWelcomePageSelectTransaction();
        if (! $fetchWelcomePageSelectTransaction->success) {
            return $fetchWelcomePageSelectTransaction;
        }

        return (object)[
            'success' => true,
            'response' => null,
        ];
    }

    protected function fetchLanding()
    {
        $cookie = $this->scraper->getCookieJar();
        $options = [
            'cookies' => $cookie,
        ];

        $curlRequest = $this->scraper->request('GET', '/', $options);
        if (! $curlRequest->success) {
            return $curlRequest;
        }

        $response = $curlRequest->data;
        // $response = file_get_contents(__DIR__.'/../../tests/temp/200910-landing');

        $parsedLanding = $this->parseLanding($response);

        $response = $parsedLanding->data;

        return (object)[
            'success' => true,
            'data' => $response,
        ];
    }

    protected function requestLogin(array $credentials = [])
    {
        $userAgent = isset($this->scraper->getCurl()->getConfig()['headers']['User-Agent'])
            ? $this->scraper->getCurl()->getConfig()['headers']['User-Agent']
            : 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.83 Safari/537.36';

        $ipAddress = isset($credentials['ipAddress'])
            ? $credentials['ipAddress']
            : $this->scraper->getIpAddress();

        $formParams = [
            'value(actions)' => 'login',
            'value(user_id)' => $credentials['username'],
            'value(user_ip)' => $ipAddress,
            'value(browser_info)' => $userAgent,
            'value(mobile)' => 'false',
            'value(pswd)' => $credentials['password'],
            'value(Submit)' => 'LOGIN',
        ];

        $cookie = $this->scraper->getCookieJar();
        $options = [
            'cookies' => $cookie,
            'form_params' => $formParams,
        ];

        $curlRequest = $this->scraper->request('POST', 'authentication.do', $options);
        if (! $curlRequest->success) {
            return $curlRequest;
        }

        $response = $curlRequest->data;
        return (object)[
            'success' => false,
            'data' => $response,
        ];
    }

    protected function fetchWelcomePage()
    {
        $cookie = $this->scraper->getCookieJar();
        $options = [
            'cookies' => $cookie,
            'query' => [
                'value(actions)' => 'welcome'
            ],
        ];
        $curlRequest = $this->scraper->request('GET', '/authentication.do', $options);
        if (! $curlRequest->success) {
            return $curlRequest;
        }

        $response = $curlRequest->data;

        return (object)[
            'success' => true,
            'data' => $response,
        ];
    }

    protected function fetchWelcomePageSelectTransaction()
    {
        $cookie = $this->scraper->getCookieJar();
        $options = [
            'cookies' => $cookie,
            'query' => [
                'value(actions)' => 'welcome',
            ],
            'form_params' => [
                'value(actions)' => 'selecttransaction',
            ],
        ];
        $curlRequest = $this->scraper->request('POST', '/authentication.do', $options);
        if (! $curlRequest->success) {
            return $curlRequest;
        }

        $response = $curlRequest->data;

        return (object)[
            'success' => true,
            'data' => $response,
        ];

    }

    public function logout()
    {
        $cookie = $this->scraper->getCookieJar();
        $options = [
            'cookies' => $cookie,
            'form_params' => [
                'value(actions)' => 'logout'
            ]
        ];
        $curlRequest = $this->scraper->request('POST', '/authentication.do', $options);
        if (! $curlRequest->success) {
            return $curlRequest;
        }

        $response = $curlRequest->data;

        return (object)[
            'success' => true,
            'data' => $response,
        ];
    }

    public function parseLanding($html = null)
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $doc = new \DOMDocument;
        // $doc->loadHTMLFile(__DIR__.'/temp/200909-landing');
        $doc->loadHTML($html);
        $doc->normalizeDocument();

        $xpath = new \DOMXPath($doc);

        $userIp = $xpath->query('//input[@name="value(user_ip)"]')->length > 0
            ? $xpath->query('//input[@name="value(user_ip)"]')[0]->getAttribute('value')
            : null;

        $data = [
            'ipAddress' => $userIp,
        ];

        return (object)[
            'success' => true,
            'data' => $data
        ];
    }
}