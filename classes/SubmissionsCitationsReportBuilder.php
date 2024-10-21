<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use PKP\cache\CacheManager;
use APP\facades\Repo;
use APP\submission\Submission;
use APP\plugins\reports\submissionsCitationsReport\classes\CrossrefClient;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionsCitationsReport;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionWithCitations;

class SubmissionsCitationsReportBuilder
{
    public function createReport($context): SubmissionsCitationsReport
    {
        $submissionsWithCitations = $this->getSubmissionsWithCitations($context->getId());
        $report = new SubmissionsCitationsReport($context->getId(), $submissionsWithCitations);

        return $report;
    }

    private function getSubmissionsWithCitations(int $contextId): array
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $contextId,
            'submissions_with_citations',
            [$this, 'cacheDismiss']
        );

        $submissionsIds = $cache->getContents();
        if (is_null($submissionsIds)) {
            $cache->flush();

            $submissionsWithCitations = $this->retrieveSubmissionsWithCitations($contextId);
            $cache->setEntireCache($submissionsWithCitations);

            return $submissionsWithCitations;
        }

        foreach ($submissionsWithCitations as $submissionWithCitations) {
            $submission = Repo::submission()->get($submissionWithCitations->getSubmissionId());
            $submissionWithCitations->setSubmission($submission);

            $submissionsWithCitations[] = $submissionWithCitations;
        }

        return $submissionsWithCitations;
    }

    public function retrieveSubmissionsWithCitations(int $contextId): array
    {
        $collector = Repo::submission()->getCollector();
        $submissions = $collector
            ->filterByContextIds([$contextId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy($collector::ORDERBY_DATE_SUBMITTED, $collector::ORDER_DIR_ASC)
            ->getMany()
            ->toArray();

        $crossrefClient = new CrossrefClient();
        $submissionsCrossrefCitationsCount = $crossrefClient->getSubmissionsCitationsCount($submissions);

        $submissionsWithCitations = [];
        foreach ($submissions as $submission) {
            $crossrefCitationsCount = $submissionsCrossrefCitationsCount[$submission->getId()];

            if ($crossrefCitationsCount > 0) {
                $submissionWithCitations = new SubmissionWithCitations();
                $submissionWithCitations->setSubmissionId($submission->getId());
                $submissionWithCitations->setCrossrefCitationsCount($crossrefCitationsCount);

                $submissionsWithCitations[$submission->getId()] = $submissionWithCitations;
            }
        }

        return $submissionsWithCitations;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
