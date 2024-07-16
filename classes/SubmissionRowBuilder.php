<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

class SubmissionRowBuilder
{
    public function buildRow($submission): array
    {
        $publication = $submission->getCurrentPublication();

        $submissionId = $submission->getId();
        $title = $publication->getLocalizedFullTitle();
        $authors = $this->getAuthorsString($publication);
        $doi = $publication->getDoi();

        return [
            $submissionId,
            $title,
            $authors,
            $doi
        ];
    }

    private function getAuthorsString($publication): string
    {
        $authorsNames = [];

        foreach ($publication->getData('authors') as $author) {
            $authorsNames[] = $author->getFullName();
        }

        return implode('; ', $authorsNames);
    }
}
