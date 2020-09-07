<?php

use PHPUnit\Framework\TestCase;

class InquiryTest extends TestCase
{
    protected $configuration;
    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = [];
    }

    public function testInquiry()
    {
        $scraper = new Scraper($this->configuration);
        $auth = new Authentication($scraper);
        $auth->login();

        $inquiry = new Inquiry($scraper);
        $transactions = $inquiry->inquiry($dateStart, $dateEnd);

        $auth->logout();
    }
}