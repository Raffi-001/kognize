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

    /**
     * Placeholders that can be used in the subject and intro, replaced for each recipient.
     *
     * @var array<string, string>
     */
    public const array TOKENS = [
        'user.name' => 'Full name',
        'user.first_name' => 'First name',
    ];

    public function __construct(
        public readonly Newsletter $newsletter,
        public readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->personalize($this->newsletter->subject),
        );
    }

    public function content(): Content
    {
        $sections = [
            'Վերլուծություններ' => $this->newsletter->submissions()->get()->map(fn (Submission $submission): array => [
                'title' => $this->escape($submission->displayTitle()),
                'url' => route('submissions.show', $submission),
            ]),
            'Հարցումներ' => $this->newsletter->polls()->get()->map(fn (Poll $poll): array => [
                'title' => $this->escape($poll->question),
                'url' => route('polls.index'),
            ]),
            'Բաց հարցեր' => $this->newsletter->questions()->get()->map(fn (AnalysisNote $question): array => [
                'title' => $this->escape($question->aspect_label ?? $question->text),
                'url' => route('questions.show', $question),
            ]),
        ];

        return new Content(
            markdown: 'mail.newsletter',
            with: [
                'heading' => $this->personalize($this->newsletter->subject),
                'intro' => $this->newsletter->intro === null ? null : $this->personalize($this->newsletter->intro),
                'sections' => array_filter($sections, fn (Collection $links): bool => $links->isNotEmpty()),
            ],
        );
    }

    /**
     * Replace tokens like {{ user.name }} with the recipient's details. Unknown tokens are left as they are.
     */
    private function personalize(string $text): string
    {
        $values = [
            'user.name' => $this->user->name,
            'user.first_name' => Str::before(Str::squish($this->user->name), ' '),
        ];

        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_.]+)\s*\}\}/',
            fn (array $match): string => $values[$match[1]] ?? $match[0],
            $text,
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
