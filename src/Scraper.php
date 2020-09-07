<?php

namespace BcaPersonal;

class Scraper
{
    use BaseRequest;

    public function __construct(array $configuration = [])
    {
        $this->setConfiguration($configuration);
    }
}