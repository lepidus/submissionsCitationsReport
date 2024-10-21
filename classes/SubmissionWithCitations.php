<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use PKP\core\DataObject;

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

    public function setCrossrefCitationsCount(int $crossrefCitationsCount)
    {
        $this->setData('crossrefCitationsCount', $crossrefCitationsCount);
    }

    public function getCrossrefCitationsCount(): int
    {
        return $this->getData('crossrefCitationsCount');
    }

}
