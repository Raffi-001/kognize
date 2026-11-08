<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\AnalysisNote;
use App\Models\Newsletter;
use App\Models\Poll;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class NewsletterMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Newsletter $newsletter,
        public readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->newsletter->subject,
        );
    }

    public function content(): Content
    {
        $sections = [
            'Submissions' => $this->newsletter->submissions()->get()->map(fn (Submission $submission): array => [
                'title' => $this->escape($submission->displayTitle()),
                'url' => route('submissions.show', $submission),
            ]),
            'Polls' => $this->newsletter->polls()->get()->map(fn (Poll $poll): array => [
                'title' => $this->escape($poll->question),
                'url' => route('polls.index'),
            ]),
            'Open Questions' => $this->newsletter->questions()->get()->map(fn (AnalysisNote $question): array => [
                'title' => $this->escape($question->aspect_label ?? $question->text),
                'url' => route('questions.show', $question),
            ]),
        ];

        return new Content(
            markdown: 'mail.newsletter',
            with: [
                'sections' => array_filter($sections, fn (Collection $links): bool => $links->isNotEmpty()),
            ],
        );
    }

    /**
     * Keep titles on one line and stop brackets from breaking the Markdown links.
     */
    private function escape(string $title): string
    {
        return str_replace(['[', ']'], ['\[', '\]'], Str::squish($title));
    }
}
