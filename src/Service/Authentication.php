<?php

namespace BcaPersonal\Service;

use BcaPersonal\Scraper;

class Authentication
{
    public function __construcy(Scraper $scraper)
    {
        $this->scraper = $scraper;
    }

    public function login($credentials = [])
    {
        # code...
    }

    public function logout()
    {
        # code...
    }
}