<?php

namespace BcaPersonal\Service;

use BcaPersonal\Scraper;
use DateTimeImmutable;

class Inquiry
{
    public function __construct(Scraper $scraper)
    {
        $this->scraper = $scraper;
    }

    public function mutation(DateTimeImmutable $dateStart, DateTimeImmutable $dateEnd)
    {
        // 
    }
}