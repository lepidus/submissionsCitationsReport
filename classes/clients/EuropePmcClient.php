<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes\clients;

use APP\core\Application;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Pool;
use GuzzleHttp\Exception\ClientException;

class EuropePmcClient
{
    private $guzzleClient;

    public const EUROPE_PMC_API_URL = 'https://www.ebi.ac.uk/europepmc/webservices/rest';

    public function __construct($guzzleClient = null)
    {
        if (!is_null($guzzleClient)) {
            $this->guzzleClient = $guzzleClient;
        } else {
            $this->guzzleClient = Application::get()->getHttpClient();
        }
    }

    public function getSubmissionsIdAndSource(array $submissions): array
    {
        $requests = [];
        $idsAndSources = [];

        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            $doi = $publication->getDoi();

            if (is_null($doi)) {
                $idsAndSources[$submission->getId()] = [];
                continue;
            }

            $requestUrl = htmlspecialchars(self::CROSSREF_API_URL . "/search?query=$doi&format=json");
            $requests[$submission->getId()] = new Request(
                'GET',
                $requestUrl,
                [
                    'headers' => ['Accept' => 'application/json'],
                ]
            );
        }

        $pool = new Pool($this->guzzleClient, $requests, [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use (&$idsAndSources, $submissions) {
                $responseJson = json_decode($response->getBody(), true);
                $results = $responseJson['resultList']['result'];

                // TODO
            },
            'rejected' => function ($reason, $index) use (&$idsAndSources) {
                $idsAndSources[$index] = [];
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $idsAndSources;
    }
}
