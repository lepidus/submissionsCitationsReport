<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use PKP\core\DataObject;
use APP\submission\Submission;

class SubmissionWithCitations extends DataObject
{
    public function setSubmissionId(int $submissionId)
    {
        $this->setData('submissionId', $submissionId);
    }

    public function getSubmissionId(): int
    {
        return $this->getData('submissionId');
    }

    public function setSubmission(Submission $submission)
    {
        $this->setData('submission', $submission);
    }

    public function getSubmission(): Submission
    {
        return $this->getData('submission');
    }

    public function setCrossrefCitationsCount(int $crossrefCitationsCount)
    {
        $this->setData('crossrefCitationsCount', $crossrefCitationsCount);
    }

    public function getCrossrefCitationsCount(): int
    {
        return $this->getData('crossrefCitationsCount');
    }

    public static function __set_state($dump)
    {
        $obj = new SubmissionWithCitations();
        $obj->setAllData($dump['_data']);

        return $obj;
    }
}
