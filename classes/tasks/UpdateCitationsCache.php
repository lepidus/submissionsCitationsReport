<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes\tasks;

use PKP\scheduledTask\ScheduledTask;
use APP\core\Services;
use PKP\cache\CacheManager;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionsCitationsReportBuilder;
use APP\plugins\reports\submissionsCitationsReport\SubmissionsCitationsReportPlugin;

class UpdateCitationsCache extends ScheduledTask
{
    public function executeActions()
    {
        $plugin = new SubmissionsCitationsReportPlugin();
        $contextIds = Services::get('context')->getIds([
            'isEnabled' => true,
        ]);

        foreach ($contextIds as $contextId) {
            if (!$plugin->getEnabled($contextId)) {
                continue;
            }

            $cacheManager = CacheManager::getManager();
            $cache = $cacheManager->getFileCache(
                $contextId,
                'submissions_with_citations',
                [$this, 'cacheDismiss']
            );

            $cache->flush();
            $reportBuilder = new SubmissionsCitationsReportBuilder();
            $submissionsWithCitations = $reportBuilder->retrieveSubmissionsWithCitations($contextId);
            $cache->setEntireCache($submissionsWithCitations);
        }

        return true;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
