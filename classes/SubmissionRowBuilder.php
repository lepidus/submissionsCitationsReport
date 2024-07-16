<?php

namespace APP\plugins\reports\submissionsCitationsReport\classes;

use APP\core\Application;

class SubmissionRowBuilder
{
    public function buildRow($context, $submission): array
    {
        $publication = $submission->getCurrentPublication();

        $submissionId = $submission->getId();
        $title = $publication->getLocalizedFullTitle();
        $authors = $this->getAuthorsString($publication);
        $url = $this->getSubmissionUrl($context, $submission);
        $doi = $publication->getDoi();

        return [
            $submissionId,
            $title,
            $authors,
            $url,
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

    private function getSubmissionUrl($context, $submission): string
    {
        $request = Application::get()->getRequest();

        return $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            $context->getPath(),
            'workflow',
            'access',
            $submission->getId()
        );
    }
}
