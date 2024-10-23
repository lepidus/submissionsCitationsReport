<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use APP\core\Application;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionRowBuilder;

class SubmissionsCitationsReport
{
    private $contextId;
    private $submissionsWithCitations;
    private $UTF8_BOM;

    public function __construct(int $contextId, array $submissionsWithCitations)
    {
        $this->contextId = $contextId;
        $this->submissionsWithCitations = $submissionsWithCitations;
        $this->UTF8_BOM = chr(0xEF) . chr(0xBB) . chr(0xBF);
    }

    public function getSubmissionsWithCitations(): array
    {
        return $this->submissionsWithCitations;
    }

    private function getHeaders(): array
    {
        return [
            __('common.id'),
            __('common.title'),
            __('submission.authors'),
            __('common.url'),
            __('metadata.property.displayName.doi'),
            __('plugins.reports.submissionsCitationsReport.scieloJournal'),
            __('plugins.reports.submissionsCitationsReport.crossrefCitationsCount'),
            __('plugins.reports.submissionsCitationsReport.europePmcCitationsCount')
        ];
    }

    public function buildCSV($fileDescriptor): void
    {
        $submissionRowBuilder = new SubmissionRowBuilder();
        $context = Application::get()->getContextDAO()->getById($this->contextId);

        fprintf($fileDescriptor, $this->UTF8_BOM);
        fputcsv($fileDescriptor, $this->getHeaders());

        foreach ($this->submissionsWithCitations as $submissionWithCitations) {
            fputcsv($fileDescriptor, $submissionRowBuilder->buildRow($context, $submissionWithCitations));
        }
    }
}
