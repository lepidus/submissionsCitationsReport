<?php

namespace APP\plugins\reports\submissionsCitationsReport\tests;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use APP\submission\Submission;
use APP\publication\Publication;

class CrossrefClientTest extends TestCase
{
    private $mockGuzzleClient;
    private $crossrefClient;
    private $contextId = 1;

    public function setUp(): void
    {
        $this->mockGuzzleClient = $this->createMockGuzzleClient();
        $this->crossrefClient = new CrossrefClient($this->mockGuzzleClient);
    }

    private function createMockGuzzleClient()
    {
        $responseBody = [
            'status' => 'ok',
            'message' => [
                'total-results' => 1,
                'items' => [
                    [
                        'DOI' => '10.666/949494',
                        'is-referenced-by-count' => 2
                    ]
                ]
            ]
        ];
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($responseBody))
        ]);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        return $guzzleClient;
    }

    private function createTestSubmission(): Submission
    {
        $doiObject = Repo::doi()->newDataObject([
            'doi' => '10.666/949494',
            'contextId' => $this->contextId
        ]);

        $submission = new Submission();
        $publication = new Publication();
        $publication->setData('id', 789);
        $publication->setData('doiObject', $doiObject);

        $submission->setData('currentPublicationId', $publication->getId());
        $submission->setData('publications', [$publication]);
        return $submission;
    }

    public function testGetSubmissionCitationsCount()
    {
        $submission = $this->createTestSubmission();
        $submissionCitationsCount = $this->crossrefClient->getSubmissionCitationsCount($submission);
        $expectedCitationsCount = 2;

        $this->assertEquals($expectedCitationsCount, $submissionCitationsCount);
    }
}
