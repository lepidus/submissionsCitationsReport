<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use APP\core\Application;
use GuzzleHttp\Promise;
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
        $promises = [];
        $citationsCount = [];

        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            $doi = $publication->getDoi();

            if (is_null($doi)) {
                $citationsCount[$submission->getId()] = 0;
                continue;
            }

            $requestUrl = htmlspecialchars(self::CROSSREF_API_URL . "?filter=doi:$doi");
            $promises[$submission->getId()] = $this->guzzleClient->requestAsync(
                'GET',
                $requestUrl,
                [
                    'headers' => ['Accept' => 'application/json'],
                ]
            );
        }

        $results = Promise\Utils::settle($promises)->wait();

        foreach ($results as $submissionId => $result) {
            if ($result['state'] == 'rejected') {
                $citationsCount[$submissionId] = 0;
                continue;
            }

            $responseJson = json_decode($result['value']->getBody(), true);
            $items = $responseJson['message']['items'];

            if (empty($items)) {
                $citationsCount[$submissionId] = 0;
                continue;
            }

            $citationsCount[$submissionId] = ((int) $items[0]['is-referenced-by-count']);
        }

        return $citationsCount;
    }
}
