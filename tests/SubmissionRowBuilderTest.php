<?php

namespace APP\plugins\reports\submissionsCitationsReport\tests;

use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use APP\facades\Repo;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\TestCase;
use APP\plugins\reports\submissionsCitationsReport\classes\SubmissionRowBuilder;

class SubmissionRowBuilderTest extends TestCase
{
    private $submission;
    private $publication;
    private $authors;
    private $locale = 'en';
    private $contextId = 13;

    public function setUp(): void
    {
        $this->authors = $this->createTestAuthors();
        $this->submission = $this->createTestSubmission();
        $this->publication = $this->createTestPublication();

        $this->submission->setData('publications', [$this->publication]);
        $this->submission->setData('currentPublicationId', $this->publication->getId());
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
        $submission = new Submission();

        $submission->setData('id', 1234);
        $submission->setData('contextId', 1);

        return $submission;
    }

    private function createTestPublication(): Publication
    {
        $publication = new Publication();
        $doiObject = Repo::doi()->newDataObject([
            'doi' => '10.666/949494',
            'contextId' => $this->contextId
        ]);

        $publication->setAllData([
            'id' => 1314,
            'submissionId' => $this->submission->getId(),
            'title' => [$this->locale => 'Advancements in rocket science'],
            'authors' => $this->lazyCollectionFromAuthors($this->authors),
            'doiObject' => $doiObject
        ]);

        return $publication;
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

    public function testBuildSubmissionRow(): void
    {
        $rowBuilder = new SubmissionRowBuilder();
        $expectedRow = [
            $this->submission->getId(),
            $this->publication->getLocalizedFullTitle($this->locale),
            'Bernard Summer; Gillian Gilbert',
            '10.666/949494'
        ];

        $this->assertEquals($expectedRow, $rowBuilder->buildRow($this->submission));
    }
}
