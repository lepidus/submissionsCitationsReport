<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use APP\core\Application;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionRowBuilder;

class SubmissionsCitationsReport
{
    private $contextId;
    private $submissions;
    private $UTF8_BOM;

    public function __construct(int $contextId, array $submissions)
    {
        $this->contextId = $contextId;
        $this->submissions = $submissions;
        $this->UTF8_BOM = chr(0xEF) . chr(0xBB) . chr(0xBF);
    }

    public function getSubmissions(): array
    {
        return $this->submissions;
    }

    private function getHeaders(): array
    {
        return [
            __('common.id'),
            __('common.title'),
            __('submission.authors'),
            __('common.url'),
            __('metadata.property.displayName.doi'),
            __('plugins.reports.submissionsCitationsReport.scieloJournal')
        ];
    }

    public function buildCSV($fileDescriptor): void
    {
        $submissionRowBuilder = new SubmissionRowBuilder();
        $context = Application::get()->getContextDAO()->getById($this->contextId);

        fprintf($fileDescriptor, $this->UTF8_BOM);
        fputcsv($fileDescriptor, $this->getHeaders());

        foreach ($this->submissions as $submission) {
            fputcsv($fileDescriptor, $submissionRowBuilder->buildRow($context, $submission));
        }
    }
}
