<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

class SubmissionsCitationsReport
{
    private $submissions;
    private $UTF8_BOM;

    public function __construct(array $submissions)
    {
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
        fprintf($fileDescriptor, $this->UTF8_BOM);
        fputcsv($fileDescriptor, $this->getHeaders());
    }
}
