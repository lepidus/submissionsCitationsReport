<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use APP\core\Application;
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
            $guzzleClient = Application::get()->getHttpClient();
        }
    }

    public function getSubmissionCitationsCount($submission): int
    {
        $publication = $submission->getCurrentPublication();
        $doi = $publication->getDoi();

        if (is_null($doi)) {
            return 0;
        }

        $requestUrl = htmlspecialchars(self::CROSSREF_API_URL . "?filter=doi:$doi");

        try {
            $response = $this->guzzleClient->request(
                'GET',
                $requestUrl,
                [
                    'headers' => ['Accept' => 'application/json'],
                ]
            );
        } catch (ClientException $exception) {
            error_log("Error while trying to get submission citations count");
            error_log($exception->getMessage());
            return 0;
        }

        $responseJson = json_decode($response->getBody(), true);
        $items = $responseJson['message']['items'];

        if (empty($items)) {
            return 0;
        }

        return ((int) $items[0]['is-referenced-by-count']);
    }
}
