<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UrlSubmissionStatus;
use App\Models\Submission;
use App\Models\UrlSubmission;
use App\Services\ArticleExtractor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

final readonly class ProcessUrlSubmission
{
    public function __construct(
        private ArticleExtractor $extractor,
        private CreateSubmission $createSubmission,
        private MarkUrlSubmissionFailed $markFailed,
    ) {}

    public function handle(UrlSubmission $urlSubmission): ?Submission
    {
        $urlSubmission->update(['status' => UrlSubmissionStatus::Processing]);

        try {
            $article = $this->extractor->extract($urlSubmission->url);
        } catch (ConnectionException|RequestException) {
            $this->markFailed->handle($urlSubmission, 'The page could not be downloaded.');

            return null;
        }

        if ($article === null) {
            $this->markFailed->handle($urlSubmission, 'Could not extract enough article content from this page.');

            return null;
        }

        $submission = $this->createSubmission->handle(
            $urlSubmission->user,
            $article['body'],
            $urlSubmission->url,
            $article['title'],
        );

        $urlSubmission->update([
            'status' => UrlSubmissionStatus::Completed,
            'submission_id' => $submission->id,
            'error_message' => null,
        ]);

        return $submission;
    }
}
