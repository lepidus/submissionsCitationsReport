<?php

namespace APP\plugins\reports\submissionsCitationsReport\tests;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\facades\Repo;
use APP\plugins\reports\submissionsCitationsReport\classes\clients\EuropePmcClient;

class EuropePmcClientTest extends TestCase
{
    private $contextId = 1;
    private $mapIdsAndSources = [
        '10.666/949494' => ['id' => '123456789', 'source' => 'MED'],
        null => [],
        '10.987/131415' => ['id' => '102132435', 'source' => 'MED']
    ];
    private $submissions;

    public function setUp(): void
    {
        $this->submissions = $this->createTestSubmissions();
    }

    private function createMockGuzzleClient($responses)
    {
        $mockResponses = [];

        foreach ($responses as $response) {
            $mockResponses[] = new Response($response['code'], [], json_encode($response['body']));
        }

        $mockHandler = new MockHandler($mockResponses);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        return $guzzleClient;
    }

    private function createMockClientForSearchQueries()
    {
        $responses = [];

        foreach ($this->mapIdsAndSources as $doi => $idAndSource) {
            if (empty($doi)) {
                $statusCode = 200;
                $responseBody = [
                    'errCode' => 404,
                    'errMsg' => 'No search criteria provided. Please provide a search criteria which is less than 1500 characters.'
                ];
            } else {
                $statusCode = 200;
                $responseBody = [
                    'version' => '6.9',
                    'hitCount' => 2,
                    'resultList' => [
                        'result' => [
                            [
                                'id' => $idAndSource['id'],
                                'source' => $idAndSource['source'],
                                'doi' => $doi
                            ]
                        ]
                    ]
                ];
            }

            $responses[] = [
                'code' => $statusCode,
                'body' => $responseBody
            ];
        }

        return $this->createMockGuzzleClient($responses);
    }

    private function createTestSubmissions(): array
    {
        $submissions = [];
        $submissionId = 10;

        foreach ($this->mapIdsAndSources as $doi => $citationsCount) {
            $submissions[$submissionId] = $this->createSubmission($submissionId, $doi);
            $submissionId++;
        }

        return $submissions;
    }

    private function createSubmission(int $submissionId, ?string $doi): Submission
    {
        $submission = new Submission();
        $submission->setData('id', $submissionId);
        $publication = new Publication();
        $publication->setData('id', 789);

        if (!is_nulL($doi)) {
            $doiObject = Repo::doi()->newDataObject([
                'doi' => $doi,
                'contextId' => $this->contextId
            ]);

            $publication->setData('doiObject', $doiObject);
        }

        $submission->setData('currentPublicationId', $publication->getId());
        $submission->setData('publications', [$publication]);
        return $submission;
    }

    public function testGetSubmissionQueryResult()
    {
        $mockClient = $this->createMockClientForSearchQueries();
        $europePmcClient = new EuropePmcClient($mockClient);
        $submissionsIdAndSource = $europePmcClient->getSubmissionsIdAndSource($this->submissions);

        foreach ($this->submissions as $submissionId => $submission) {
            $doi = $submission->getCurrentPublication()->getDoi();
            $expectedIdAndSource = $this->mapIdsAndSources[$doi];

            $this->assertEquals($expectedIdAndSource, $submissionsIdAndSource[$submissionId]);
        }
    }
}
