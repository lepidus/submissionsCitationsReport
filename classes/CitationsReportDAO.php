<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use PKP\db\DAO;
use APP\core\Application;
use APP\facades\Repo;
use Illuminate\Support\Facades\DB;
use PKP\log\event\PKPSubmissionEventLogEntry;

class CitationsReportDAO extends DAO
{
    public function getIdOfSubmitterUser(int $submissionId): ?int
    {
        $result = DB::table('event_log')
            ->where('event_type', PKPSubmissionEventLogEntry::SUBMISSION_LOG_SUBMISSION_SUBMIT)
            ->where('assoc_type', Application::ASSOC_TYPE_SUBMISSION)
            ->where('assoc_id', $submissionId)
            ->select('user_id')
            ->get()
            ->toArray();

        if (empty($result)) {
            return null;
        }

        return get_object_vars($result[0])['user_id'];
    }

    public function userIsScieloJournal(int $userId): bool
    {
        $userUserGroups = Repo::userGroup()->userUserGroups($userId);
        $journalGroupAbbrev = 'SciELO';

        foreach ($userUserGroups as $userGroup) {
            if ($userGroup->getLocalizedData('abbrev', 'pt_BR') == $journalGroupAbbrev) {
                return true;
            }
        }

        return false;
    }
}
