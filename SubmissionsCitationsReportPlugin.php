<?php

namespace APP\plugins\reports\submissionsCitationsReport;

use PKP\plugins\ReportPlugin;
use APP\core\Application;
use PKP\plugins\Hook;

class SubmissionsCitationsReportPlugin extends ReportPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path);
        // if ($success && $this->getEnabled()) {
        // }
        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.reports.submissionsCitationsReport.displayName');
    }

    public function getDescription()
    {
        return __('plugins.reports.submissionsCitationsReport.description');
    }
}
