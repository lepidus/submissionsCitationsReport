<?php

namespace APP\plugins\reports\submissionsCitationsReport;

use PKP\plugins\ReportPlugin;
use APP\core\Application;
use PKP\plugins\Hook;
use PKP\core\PKPString;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionsCitationsReportBuilder;

class SubmissionsCitationsReportPlugin extends ReportPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);

        if (Application::isUnderMaintenance()) {
            return $success;
        }

        if ($success && $this->getEnabled($mainContextId)) {
            $this->addLocaleData();
        }

        return $success;
    }

    public function getName()
    {
        return 'submissionscitationsreportplugin';
    }

    public function getDisplayName()
    {
        return __('plugins.reports.submissionsCitationsReport.displayName');
    }

    public function getDescription()
    {
        return __('plugins.reports.submissionsCitationsReport.description');
    }

    public function display($args, $request)
    {
        $context = $request->getContext();
        $submissionsCitationsReportBuilder = new SubmissionsCitationsReportBuilder();
        $report = $submissionsCitationsReportBuilder->createReport($context);

        $this->emitHttpHeaders($request);
        $csvFile = fopen('php://output', 'wt');
        $report->buildCSV($csvFile);
    }

    private function emitHttpHeaders($request)
    {
        $context = $request->getContext();
        header('content-type: text/comma-separated-values');
        $acronym = PKPString::regexp_replace("/[^A-Za-z0-9 ]/", '', $context->getLocalizedAcronym());
        header('content-disposition: attachment; filename=submissions_citations_' . $acronym . '_' . date('YmdHis') . '.csv');
    }
}
