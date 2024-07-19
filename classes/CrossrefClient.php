<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

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
        $response = $this->guzzleClient->request(
            'GET',
            $requestUrl,
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        $responseJson = json_decode($response->getBody(), true);
        $citationsCount = $responseJson['message']['items'][0]['is-referenced-by-count'];

        return $citationsCount;
    }
}
