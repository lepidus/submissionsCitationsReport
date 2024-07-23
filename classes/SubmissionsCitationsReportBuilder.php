<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use PKP\cache\CacheManager;
use APP\facades\Repo;
use APP\submission\Submission;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionsCitationsReport;
use APP\plugins\reports\submissionsCitationsReport\classes\CrossrefClient;

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
            $submissionsIds = array_keys($submissionsWithCitations);
            $cache->setEntireCache($submissionsIds);

            return $submissionsWithCitations;
        }

        $submissionsWithCitations = [];
        foreach ($submissionsIds as $submissionId) {
            $submissionsWithCitations[] = Repo::submission()->get($submissionId);
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
        $submissionsCitationsCount = $crossrefClient->getSubmissionsCitationsCount($submissions);

        $submissionsWithCitations = [];
        foreach ($submissions as $submission) {
            if ($submissionsCitationsCount[$submission->getId()] > 0) {
                $submissionsWithCitations[$submission->getId()] = $submission;
            }
        }

        return $submissionsWithCitations;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
