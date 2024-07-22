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
        $collector = Repo::submission()->getCollector();
        $submissions = $collector
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy($collector::ORDERBY_DATE_SUBMITTED, $collector::ORDER_DIR_ASC)
            ->getMany()
            ->toArray();

        $crossrefClient = new CrossrefClient();
        $submissionsCitationsCount = $crossrefClient->getSubmissionsCitationsCount($submissions);

        $submissionsWithCitations = [];
        foreach ($submissions as $submission) {
            if ($submissionsCitationsCount[$submission->getId()] > 0) {
                $submissionsWithCitations[] = $submission;
            }
        }

        $report = new SubmissionsCitationsReport($context->getId(), $submissionsWithCitations);

        return $report;
    }
}
