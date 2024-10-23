<?php

namespace APP\plugins\reports\submissionsCitationsReport\tests;

use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use Illuminate\Support\LazyCollection;
use APP\facades\Repo;
use PHPUnit\Framework\TestCase;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionsCitationsReport;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionWithCitations;
use APP\plugins\reports\submissionsCitationsReport\tests\CSVFileUtils;

class SubmissionsCitationsReportTest extends TestCase
{
    private $contextId = 1;
    private $submission;
    private $submissionWithCitations;
    private $locale = 'en';
    private $report;
    private $filePath = '/tmp/test_report.csv';

    public function setUp(): void
    {
        $this->submission = $this->createTestSubmission();
        $this->submissionWithCitations = $this->createSubmissionWithCitations();
        $this->report = new SubmissionsCitationsReport($this->contextId, [$this->submissionWithCitations]);
    }

    public function tearDown(): void
    {
        if (file_exists(($this->filePath))) {
            unlink($this->filePath);
        }
    }

    private function createTestSubmission(): Submission
    {
        $submission = new Submission();
        $submission->setData('id', 1234);
        $submission->setData('contextId', $this->contextId);

        $doiObject = Repo::doi()->newDataObject([
            'doi' => '10.666/949494',
            'contextId' => $this->contextId
        ]);
        $authors = $this->createTestAuthors();

        $publication = new Publication();
        $publication->setData('id', 789);
        $publication->setData('title', 'Advancements in rocket science', $this->locale);
        $publication->setData('authors', $this->lazyCollectionFromAuthors($authors));
        $publication->setData('doiObject', $doiObject);

        $submission->setData('currentPublicationId', $publication->getId());
        $submission->setData('publications', [$publication]);
        return $submission;
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

    private function lazyCollectionFromAuthors(array $authors): LazyCollection
    {
        $collectionAuthors = LazyCollection::make(function () use ($authors) {
            foreach ($authors as $author) {
                yield $author->getId() => $author;
            }
        });

        return $collectionAuthors;
    }

    private function createSubmissionWithCitations(): SubmissionWithCitations
    {
        $submissionWithCitations = new SubmissionWithCitations();
        $submissionWithCitations->setSubmissionId($this->submission->getId());
        $submissionWithCitations->setSubmission($this->submission);
        $submissionWithCitations->setCrossrefCitationsCount(52);
        $submissionWithCitations->setEuropePmcCitationsCount(17);

        return $submissionWithCitations;
    }

    private function generateCSV(): void
    {
        $csvFile = fopen($this->filePath, 'wt');
        $this->report->buildCSV($csvFile);
        fclose($csvFile);
    }

    public function testGeneratedCsvHasUtf8Bytes(): void
    {
        $this->generateCSV();
        $csvFile = fopen($this->filePath, 'r');
        $csvFileUtils = new CSVFileUtils();
        $byteRead = $csvFileUtils->readUTF8Bytes($csvFile);
        fclose($csvFile);

        $this->assertEquals($csvFileUtils->getExpectedUTF8BOM(), $byteRead);
    }

    public function testGeneratedCsvHasHeaders(): void
    {
        $this->generateCSV();
        $csvFile = fopen($this->filePath, 'r');
        $csvFileUtils = new CSVFileUtils();
        $csvFileUtils->readUTF8Bytes($csvFile);

        $firstRow = fgetcsv($csvFile);
        fclose($csvFile);
        $expectedRow = [
            __('common.id'),
            __('common.title'),
            __('submission.authors'),
            __('common.url'),
            __('metadata.property.displayName.doi'),
            __('plugins.reports.submissionsCitationsReport.scieloJournal'),
            __('plugins.reports.submissionsCitationsReport.crossrefCitationsCount'),
            __('plugins.reports.submissionsCitationsReport.europePmcCitationsCount')
        ];

        $this->assertEquals($expectedRow, $firstRow);
    }

    public function testGeneratedCsvHasSubmissionRow(): void
    {
        $this->generateCSV();
        $csvFile = fopen($this->filePath, 'r');
        $csvFileUtils = new CSVFileUtils();
        $csvFileUtils->readUTF8Bytes($csvFile);

        fgetcsv($csvFile);
        $secondRow = fgetcsv($csvFile);
        fclose($csvFile);

        $submissionId = $this->submission->getId();
        $publication = $this->submission->getCurrentPublication();

        $expectedRow = [
            $submissionId,
            $publication->getLocalizedFullTitle($this->locale),
            'Bernard Summer; Gillian Gilbert',
            "https://pkp.sfu.ca/ops/index.php/publicknowledge/workflow/access/$submissionId",
            '10.666/949494',
            __('common.no'),
            52,
            17
        ];

        $this->assertEquals($expectedRow, $secondRow);
    }
}
