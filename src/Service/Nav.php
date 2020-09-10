<?php

namespace BcaPersonal\Service;

use BcaPersonal\Scraper;

class Nav
{
    protected $scraper;

    public function __construct(Scraper $scraper)
    {
        $this->scraper = $scraper;
    }

    public function navbarAccountInformation()
    {
        $cookie = $this->scraper->getCookieJar();
        $options = [
            'cookies' => $cookie,
        ];

        $curlRequest = $this->scraper->request('GET', '/nav_bar/account_information_menu.htm', $options);
        if (! $curlRequest->success) {
            return $curlRequest;
        }

        $response = $curlRequest->data;

        return (object)[
            'success' => true,
            'data' => $response,
        ];
    }
}