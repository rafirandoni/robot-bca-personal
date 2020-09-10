<?php

namespace BcaPersonal\Service;

use BcaPersonal\Scraper;
use DateTimeImmutable;

class Inquiry
{
    protected $scraper;
    protected $navService;

    public function __construct(Scraper $scraper)
    {
        $this->scraper = $scraper;
        $this->navService = new Nav($scraper);
    }

    public function mutation(DateTimeImmutable $dateStart, DateTimeImmutable $dateEnd)
    {
        // Get navbar account information
        $navbar = $this->navService->navbarAccountInformation();
        if (! $navbar->success) {
            return $navbar;
        }

        // Get account statement page
        $account = $this->fetchAccountStatement();
        if (! $account->success) {
            return $account;
        }

        // Inquiry statement
        $statements = $this->fetchStatementLists($dateStart, $dateEnd);
        if (! $statements->success) {
            return $statements;
        }

        return (object)[
            'success' => true,
            'message' => $statements->data
        ];
    }

    protected function fetchAccountStatement()
    {
        $cookie = $this->scraper->getCookieJar();
        $options = [
            'cookies' => $cookie,
        ];
        $curlRequest = $this->scraper->request('POST', '/accountstmt.do?value(actions)=acct_stmt', $options);
        if (! $curlRequest->success) {
            return $curlRequest;
        }

        $response = $curlRequest->data;

        return (object)[
            'success' => true,
            'message' => $response
        ];
    }

    protected function fetchStatementLists(DateTimeImmutable $dateStart, DateTimeImmutable $dateEnd)
    {
        $formParams = [
            'value(D1)' => '0',
            'value(r1)' => '1',
            'value(startDt)' => $dateStart->format('d'),
            'value(startMt)' => $dateStart->format('m'),
            'value(startYr)' => $dateStart->format('Y'),
            'value(endDt)' => $dateEnd->format('d'),
            'value(endMt)' => $dateEnd->format('m'),
            'value(endYr)' => $dateEnd->format('Y'),
            'value(fDt)' => '',
            'value(tDt)' => '',
            'value(submit1)' => 'View Account Statement',
        ];

        $cookie = $this->scraper->getCookieJar();
        $options = [
            'cookies' => $cookie,
            'form_params' => $formParams,
        ];
        $curlRequest = $this->scraper->request('POST', '/accountstmt.do?value(actions)=acctstmtview', $options);
        if (! $curlRequest->success) {
            return $curlRequest;
        }

        $response = $curlRequest->data;

        $parsedResponse = $this->parseStatementList($response);

        return (object)[
            'success' => true,
            'data' => $parsedResponse->data,
        ];
    }

    protected function parseStatementList($html)
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $doc = new \DOMDocument;
        $doc->loadHTML($html);
        $doc->normalizeDocument();

        $xpath = new \DOMXPath($doc);

        // var_dump($xpath->query()); exit;

        $mutationTable = $xpath->query('//table')->length > 0
            ? $xpath->query('//table')[4]
            : null;

        if (! $mutationTable) {
            return (object)[
                'success' => false,
                'message' => 'Mutasi tidak ditemukan'
            ];
        }

        $data = [];
        if ($row = $mutationTable->getElementsByTagName('tr')) {
            $startRow = 1;
            for ($i=$startRow; $i < $row->length; $i++) {
                if ($cols = $mutationTable->getElementsByTagName('tr')[$i]->getElementsByTagName('td')) {
                    $data[] = (object)[
                        'date' => trim($cols[0]->textContent),
                        'description' => trim($cols[1]->textContent),
                        'branch' => trim($cols[2]->textContent),
                        'amount' => trim($cols[3]->textContent),
                        'entry' => trim($cols[4]->textContent),
                        'balance' => trim($cols[5]->textContent),
                    ];
                }
            }
        }

        return (object)[
            'success' => true,
            'data' => $data
        ];
    }
}