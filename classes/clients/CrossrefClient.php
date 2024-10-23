<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes\clients;

use APP\core\Application;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Pool;
use GuzzleHttp\Exception\ClientException;

class CrossrefClient
{
    private $guzzleClient;

    public const CROSSREF_API_URL = 'https://api.crossref.org/works';

    public function __construct($guzzleClient = null)
    {
        if (!is_null($guzzleClient)) {
            $this->guzzleClient = $guzzleClient;
        } else {
            $this->guzzleClient = Application::get()->getHttpClient();
        }
    }

    public function getSubmissionsCitationsCount(array $submissions): array
    {
        $requests = [];
        $citationsCount = [];

        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            $doi = $publication->getDoi();

            if (empty($doi)) {
                $citationsCount[$submission->getId()] = 0;
                continue;
            }

            $requestUrl = htmlspecialchars(self::CROSSREF_API_URL . "?filter=doi:$doi");
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
            'fulfilled' => function ($response, $index) use (&$citationsCount) {
                $responseJson = json_decode($response->getBody(), true);
                $items = $responseJson['message']['items'];

                if (empty($items)) {
                    $citationsCount[$index] = 0;
                    return;
                }

                $citationsCount[$index] = (int) $items[0]['is-referenced-by-count'];
            },
            'rejected' => function ($reason, $index) use (&$citationsCount) {
                $citationsCount[$index] = 0;
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $citationsCount;
    }
}
