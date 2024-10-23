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

    public function getSubmissionsCitationsCount(array $submissions): array
    {
        $idsAndSources = $this->getSubmissionsIdAndSource($submissions);
        $submissionsCitationsCount = $this->getCitationsCountByIdAndSource($idsAndSources);

        return $submissionsCitationsCount;
    }

    public function getSubmissionsIdAndSource(array $submissions): array
    {
        $requests = [];
        $idsAndSources = [];

        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            $doi = $publication->getDoi();

            if (empty($doi)) {
                $idsAndSources[$submission->getId()] = [];
                continue;
            }

            $requestUrl = htmlspecialchars(self::EUROPE_PMC_API_URL . "/search?query=DOI:$doi") . '&format=json';
            $requests[$submission->getId()] = new Request(
                'GET',
                $requestUrl
            );
        }

        $pool = new Pool($this->guzzleClient, $requests, [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use (&$idsAndSources, $submissions) {
                $responseBody = json_decode($response->getBody(), true);
                $idAndSource = [];

                if (isset($responseBody['resultList'])) {
                    $results = $responseBody['resultList']['result'];
                    $submission = $submissions[$index];
                    $submissionDoi = strtolower($submission->getCurrentPublication()->getDoi());

                    foreach ($results as $result) {
                        if (strtolower($result['doi']) == $submissionDoi) {
                            $idAndSource = [
                                'id' => $result['id'],
                                'source' => $result['source']
                            ];
                            break;
                        }
                    }
                }

                $idsAndSources[$index] = $idAndSource;
            },
            'rejected' => function ($reason, $index) use (&$idsAndSources) {
                $idsAndSources[$index] = [];
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $idsAndSources;
    }

    public function getCitationsCountByIdAndSource(array $submissionsIdsAndSources): array
    {
        $requests = [];
        $submissionsCitationsCount = [];

        foreach ($submissionsIdsAndSources as $submissionId => $idAndSource) {
            if (empty($idAndSource)) {
                $submissionsCitationsCount[$submissionId] = 0;
                continue;
            }

            $id = $idAndSource['id'];
            $source = $idAndSource['source'];

            $requestUrl = htmlspecialchars(self::EUROPE_PMC_API_URL . "/$source/$id/citations?format=json");
            $requests[$submissionId] = new Request(
                'GET',
                $requestUrl
            );
        }

        $pool = new Pool($this->guzzleClient, $requests, [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use (&$submissionsCitationsCount) {
                $responseJson = json_decode($response->getBody(), true);
                $citationsCount = 0;

                if (isset($responseJson['hitCount'])) {
                    $citationsCount = $responseJson['hitCount'];
                }

                $submissionsCitationsCount[$index] = $citationsCount;
            },
            'rejected' => function ($reason, $index) use (&$idsAndSources) {
                $submissionsCitationsCount[$index] = 0;
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $submissionsCitationsCount;
    }
}
