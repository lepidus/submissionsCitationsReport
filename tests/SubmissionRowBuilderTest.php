<?php

namespace APP\plugins\reports\submissionsCitationsReport\tests;

use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use APP\core\Application;
use PKP\core\Core;
use APP\facades\Repo;
use PKP\security\Role;
use PKP\log\event\PKPSubmissionEventLogEntry;
use Illuminate\Support\LazyCollection;
use PKP\tests\DatabaseTestCase;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionRowBuilder;

class SubmissionRowBuilderTest extends DatabaseTestCase
{
    private $contextId = 1;
    private $submission;
    private $authors;
    private $locale = 'en';

    public function setUp(): void
    {
        parent::setUp();

        $this->submission = $this->createTestSubmission();
        $this->editPublication();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Repo::submission()->delete($this->submission);
    }

    protected function getAffectedTables()
    {
        return ['users', 'user_groups', 'user_settings', 'user_group_settings', 'user_user_groups', 'event_log'];
    }

    private function createTestAuthors(): array
    {
        $author1 = new Author();
        $author1->setData('givenName', 'Bernard');
        $author1->setData('familyName', 'Summer');

        $author2 = new Author();
        $author2->setData('givenName', 'Gillian');
        $author2->setData('familyName', 'Gilbert');

        return [$author1, $author2];
    }

    private function createTestSubmission(): Submission
    {
        $context = Application::get()->getContextDAO()->getById($this->contextId);

        $submission = new Submission();
        $submission->setData('contextId', $this->contextId);
        $publication = new Publication();

        $submissionId = Repo::submission()->add($submission, $publication, $context);

        return Repo::submission()->get($submissionId);
    }

    private function editPublication()
    {
        $publication = $this->submission->getCurrentPublication();
        $doiObject = Repo::doi()->newDataObject([
            'doi' => '10.666/949494',
            'contextId' => $this->contextId
        ]);
        $authors = $this->createTestAuthors();

        $publication->setData('title', 'Advancements in rocket science', $this->locale);
        $publication->setData('authors', $this->lazyCollectionFromAuthors($authors));
        $publication->setData('doiObject', $doiObject);

        $this->submission->setData('publications', [$publication]);
    }

    private function lazyCollectionFromAuthors(array $authors): LazyCollection
    {
        $collectionAuthors = LazyCollection::make(function () use ($authors) {
            foreach ($authors as $author) {
                yield $author->getId() => $author;
            }
        });

        return $collectionAuthors;
    }

    private function createSubmitter(): int
    {
        $userSubmitter = Repo::user()->newDataObject([
            'userName' => 'the_godfather',
            'email' => 'donvito@corleone.com',
            'password' => 'miaumiau',
            'country' => 'BR',
            'givenName' => [$this->locale => 'Don'],
            'familyName' => [$this->locale => 'Vito Corleone'],
            'dateRegistered' => Core::getCurrentDate()
        ]);
        $userSubmitterId = Repo::user()->dao->insert($userSubmitter);

        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => Application::ASSOC_TYPE_SUBMISSION,
            'assocId' => $this->submission->getId(),
            'eventType' => PKPSubmissionEventLogEntry::SUBMISSION_LOG_SUBMISSION_SUBMIT,
            'userId' => $userSubmitterId,
            'dateLogged' => Core::getCurrentDate()
        ]);
        Repo::eventLog()->add($eventLog);

        return $userSubmitterId;
    }

    private function createScieloJournalUserGroup(): int
    {
        $scieloJournalUserGroup = Repo::userGroup()->newDataObject([
            'name' => [
                'en' => 'SciELO Journal',
                'es' => 'Revista SciELO',
                'pt_BR' => 'Periódico SciELO'
            ],
            'abbrev' => [
                'en' => 'SciELO',
                'es' => 'SciELO',
                'pt_BR' => 'SciELO'
            ],
            'roleId' => Role::ROLE_ID_SUB_EDITOR,
            'contextId' => $this->contextId
        ]);

        return Repo::userGroup()->add($scieloJournalUserGroup);
    }

    public function testBuildSubmissionRow(): void
    {
        $rowBuilder = new SubmissionRowBuilder();
        $context = Application::get()->getContextDAO()->getById($this->contextId);
        $submissionId = $this->submission->getId();
        $publication = $this->submission->getCurrentPublication();

        $expectedRow = [
            $submissionId,
            $publication->getLocalizedFullTitle($this->locale),
            'Bernard Summer; Gillian Gilbert',
            "https://pkp.sfu.ca/ops/index.php/publicknowledge/workflow/access/$submissionId",
            '10.666/949494',
        ];

        $this->assertEquals($expectedRow, $rowBuilder->buildRow($context, $this->submission));
    }

    public function testBuildSubmissionRowWithScieloJournal(): void
    {
        $submitterId = $this->createSubmitter();
        $scieloJournalGroupId = $this->createScieloJournalUserGroup();
        Repo::userGroup()->assignUserToGroup(
            $submitterId,
            $scieloJournalGroupId
        );

        $rowBuilder = new SubmissionRowBuilder();
        $context = Application::get()->getContextDAO()->getById($this->contextId);
        $submissionId = $this->submission->getId();
        $publication = $this->submission->getCurrentPublication();
        $expectedRow = [
            $submissionId,
            $publication->getLocalizedFullTitle($this->locale),
            'Bernard Summer; Gillian Gilbert',
            "https://pkp.sfu.ca/ops/index.php/publicknowledge/workflow/access/$submissionId",
            '10.666/949494',
            __('common.yes')
        ];

        $this->assertEquals($expectedRow, $rowBuilder->buildRow($context, $this->submission));
    }
}
