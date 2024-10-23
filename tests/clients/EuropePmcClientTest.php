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
    private $mapDoiToData = [
        '10.666/949494' => ['id' => '123456789', 'source' => 'MED', 'citations' => 34],
        null => ['citations' => 0],
        '10.987/131415' => ['id' => '102132435', 'source' => 'MED', 'citations' => 12]
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

        foreach ($this->mapDoiToData as $doi => $data) {
            if (empty($doi)) {
                continue;
            }

            $responses[] = [
                'code' => 200,
                'body' => [
                    'version' => '6.9',
                    'hitCount' => 2,
                    'resultList' => [
                        'result' => [
                            [
                                'id' => $data['id'],
                                'source' => $data['source'],
                                'doi' => $doi
                            ]
                        ]
                    ]
                ]
            ];
        }

        return $this->createMockGuzzleClient($responses);
    }

    private function createMockClientForCitationCount()
    {
        $responses = [];

        foreach ($this->mapDoiToData as $doi => $data) {
            if (empty($doi)) {
                continue;
            }

            $responses[] = [
                'code' => 200,
                'body' => [
                    'version' => '6.9',
                    'hitCount' => $data['citations'],
                ]
            ];
        }

        return $this->createMockGuzzleClient($responses);
    }

    private function createTestSubmissions(): array
    {
        $submissions = [];
        $submissionId = 10;

        foreach ($this->mapDoiToData as $doi => $data) {
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

        if (!empty($doi)) {
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
            $expectedIdAndSource = $this->mapDoiToData[$doi];
            unset($expectedIdAndSource['citations']);

            $this->assertEquals($expectedIdAndSource, $submissionsIdAndSource[$submissionId]);
        }
    }

    public function testGetCitationsCountByIdAndSource()
    {
        $mockClient = $this->createMockClientForCitationCount();
        $europePmcClient = new EuropePmcClient($mockClient);

        $submissionsIdsAndSources = [];
        foreach ($this->submissions as $submissionId => $submission) {
            $doi = $submission->getCurrentPublication()->getDoi();
            $idAndSource = $this->mapDoiToData[$doi];
            unset($idAndSource['citations']);

            $submissionsIdsAndSources[$submissionId] = $idAndSource;
        }

        $submissionsCitationsCount = $europePmcClient->getCitationsCountByIdAndSource($submissionsIdsAndSources);

        foreach ($this->submissions as $submissionId => $submission) {
            $doi = $submission->getCurrentPublication()->getDoi();
            $expectedCitationsCount = $this->mapDoiToData[$doi]['citations'];

            $this->assertEquals($expectedCitationsCount, $submissionsCitationsCount[$submissionId]);
        }
    }
}
