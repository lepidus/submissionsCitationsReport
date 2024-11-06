<?php

namespace APP\plugins\reports\submissionsCitationsReport\tests;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\facades\Repo;
use APP\plugins\reports\submissionsCitationsReport\classes\clients\OpenAlexClient;

class OpenAlexClientTest extends TestCase
{
    private $mockGuzzleClient;
    private $crossrefClient;
    private $contextId = 1;
    private $mapDoiCitationsCount = [
        '10.666/949494' => 42,
        null => 0,
        '10.987/131415' => 31
    ];
    private $submissions;

    public function setUp(): void
    {
        $this->submissions = $this->createTestSubmissions();
        $this->mockGuzzleClient = $this->createMockGuzzleClient();
        $this->crossrefClient = new OpenAlexClient($this->mockGuzzleClient);
    }

    private function createMockGuzzleClient()
    {
        $mockResponses = [];

        foreach ($this->mapDoiCitationsCount as $doi => $citationsCount) {
            if (empty($doi)) {
                continue;
            }

            $responseBody = [
                'id' => 'https://openalex.org/asdjakdfja',
                'doi' => "https://doi.org/$doi",
                'cited_by_count' => $citationsCount
            ];
            $mockResponses[] = new Response(200, [], json_encode($responseBody));
        }

        $mockHandler = new MockHandler($mockResponses);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        return $guzzleClient;
    }

    private function createTestSubmissions(): array
    {
        $submissions = [];
        $submissionId = 10;

        foreach ($this->mapDoiCitationsCount as $doi => $citationsCount) {
            $submissions[] = $this->createSubmission($submissionId++, $doi);
        }

        return $submissions;
    }

    private function createSubmission(int $submissionId, ?string $doi): Submission
    {
        $submission = new Submission();
        $submission->setData('id', $submissionId);
        $publication = new Publication();
        $publication->setData('id', $submissionId + 2);

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

    public function testGetSubmissionCitationsCount()
    {
        $submissionsCitationsCount = $this->crossrefClient->getSubmissionsCitationsCount($this->submissions);

        foreach ($this->submissions as $submission) {
            $doi = $submission->getCurrentPublication()->getDoi();
            $submissionId = $submission->getId();

            $this->assertEquals($this->mapDoiCitationsCount[$doi], $submissionsCitationsCount[$submissionId]);
        }
    }
}
