<?php

namespace APP\plugins\reports\submissionsCitationsReport\tests;

use PHPUnit\Framework\TestCase;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionsCitationsReport;
use APP\plugins\reports\submissionsCitationsReport\tests\CSVFileUtils;

class SubmissionsCitationsReportTest extends TestCase
{
    private $report;
    private $filePath = '/tmp/test_report.csv';

    public function setUp(): void
    {
        $this->report = new SubmissionsCitationsReport([]);
    }

    public function tearDown(): void
    {
        if (file_exists(($this->filePath))) {
            unlink($this->filePath);
        }
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

        $firstLine = fgetcsv($csvFile);
        $expectedLine = [
            __('common.id'),
            __('common.title'),
            __('submission.authors'),
            __('common.url'),
            __('metadata.property.displayName.doi'),
            __('plugins.reports.submissionsCitationsReport.scieloJournal')
        ];
        fclose($csvFile);

        $this->assertEquals($expectedLine, $firstLine);
    }
}
