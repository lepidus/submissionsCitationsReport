<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use APP\facades\Repo;
use APP\submission\Submission;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionsCitationsReport;
use APP\plugins\reports\submissionsCitationsReport\classes\CrossrefClient;

class SubmissionsCitationsReportBuilder
{
    public function createReport($context): SubmissionsCitationsReport
    {
        $submissions = Repo::submission()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->getMany();

        $crossrefClient = new CrossrefClient();
        $submissionsWithCitations = [];
        foreach ($submissions as $submission) {
            if ($crossrefClient->getSubmissionCitationsCount($submission) > 0) {
                $submissionsWithCitations[] = $submission;
            }
        }

        $report = new SubmissionsCitationsReport($context->getId(), $submissionsWithCitations);

        return $report;
    }
}
