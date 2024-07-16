<?php

namespace APP\plugins\reports\submissionsCitationsReport\tests;

use APP\server\Server;
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
    private $context;
    private $locale = 'en';

    public function setUp(): void
    {
        $this->context = $this->createTestContext();
        $this->authors = $this->createTestAuthors();
        $this->submission = $this->createTestSubmission();
        $this->publication = $this->createTestPublication();

        $this->submission->setData('publications', [$this->publication]);
        $this->submission->setData('currentPublicationId', $this->publication->getId());
    }

    private function createTestContext(): Server
    {
        $context = new Server();
        $context->setAllData([
            'id' => 13,
            'urlPath' => 'publicknowledge'
        ]);

        return $context;
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
        $submission->setData('contextId', $this->context->getId());

        return $submission;
    }

    private function createTestPublication(): Publication
    {
        $publication = new Publication();
        $doiObject = Repo::doi()->newDataObject([
            'doi' => '10.666/949494',
            'contextId' => $this->context->getId()
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
        $submissionId = $this->submission->getId();
        $expectedRow = [
            $submissionId,
            $this->publication->getLocalizedFullTitle($this->locale),
            'Bernard Summer; Gillian Gilbert',
            "https://pkp.sfu.ca/ops/index.php/publicknowledge/workflow/access/$submissionId",
            '10.666/949494'
        ];

        $this->assertEquals($expectedRow, $rowBuilder->buildRow($this->context, $this->submission));
    }
}
