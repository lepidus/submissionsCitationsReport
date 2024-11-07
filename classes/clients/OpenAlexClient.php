<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes\clients;

use APP\core\Application;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Pool;
use GuzzleHttp\Exception\ClientException;

class OpenAlexClient
{
    private $guzzleClient;

    public const OPEN_ALEX_API_URL = 'https://api.openalex.org/works';

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
            $doiObject = $publication->getData('doiObject');

            if (empty($doiObject)) {
                $citationsCount[$submission->getId()] = 0;
                continue;
            }

            $doiResolvingUrl = $doiObject->getResolvingUrl();
            $requestUrl = htmlspecialchars(self::OPEN_ALEX_API_URL . "/$doiResolvingUrl?select=doi,cited_by_count");
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
                $recordDoi = $responseJson['doi'];

                if (empty($recordDoi)) {
                    $citationsCount[$index] = 0;
                    return;
                }

                $citationsCount[$index] = (int) $responseJson['cited_by_count'];
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
