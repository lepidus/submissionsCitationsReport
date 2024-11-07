<?php

namespace APP\plugins\reports\submissionsCitationsReport\tests;

use PKP\tests\PKPTestCase;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionWithCitations;

class SubmissionWithCitationsTest extends PKPTestCase
{
    private SubmissionWithCitations $submissionWithCitations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->submissionWithCitations = $this->createSubmissionWithCitations();
    }

    private function createSubmissionWithCitations()
    {
        $submissionWithCitations = new SubmissionWithCitations();
        $submissionWithCitations->setSubmissionId(7890);
        $submissionWithCitations->setCrossrefCitationsCount(49);
        $submissionWithCitations->setEuropePmcCitationsCount(14);
        $submissionWithCitations->setOpenAlexCitationsCount(21);

        return $submissionWithCitations;
    }

    public function testGetObjectData(): void
    {
        $expectedData = [
            'submissionId' => 7890,
            'crossrefCitationsCount' => 49,
            'europePmcCitationsCount' => 14,
            'openAlexCitationsCount' => 21
        ];
        $this->assertEquals($expectedData, $this->submissionWithCitations->_data);
    }
}
