<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use PKP\cache\CacheManager;
use APP\facades\Repo;
use APP\submission\Submission;
use APP\plugins\reports\submissionsCitationsReport\classes\clients\CrossrefClient;
use APP\plugins\reports\submissionsCitationsReport\classes\clients\EuropePmcClient;
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

        $submissionsWithCitations = $cache->getContents();
        if (is_null($submissionsWithCitations)) {
            $cache->flush();

            $submissionsWithCitations = $this->retrieveSubmissionsWithCitations($contextId);
            $cache->setEntireCache($submissionsWithCitations);
        }

        foreach ($submissionsWithCitations as $submissionId => $submissionWithCitations) {
            $submission = Repo::submission()->get($submissionId);
            $submissionWithCitations->setSubmission($submission);

            $submissionsWithCitations[$submissionId] = $submissionWithCitations;
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

        $europePmcClient = new EuropePmcClient();
        $submissionsEuropePmcCitationsCount = $europePmcClient->getSubmissionsCitationsCount($submissions);

        $submissionsWithCitations = [];
        foreach ($submissions as $submission) {
            $crossrefCitationsCount = $submissionsCrossrefCitationsCount[$submission->getId()];
            $europePmcCitationsCount = $submissionsEuropePmcCitationsCount[$submission->getId()];

            if ($crossrefCitationsCount > 0 || $europePmcCitationsCount > 0) {
                $submissionWithCitations = new SubmissionWithCitations();
                $submissionWithCitations->setSubmissionId($submission->getId());
                $submissionWithCitations->setCrossrefCitationsCount($crossrefCitationsCount);
                $submissionWithCitations->setEuropePmcCitationsCount($europePmcCitationsCount);

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
