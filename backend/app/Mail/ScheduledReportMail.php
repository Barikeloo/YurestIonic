<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly array   $recipients,
        private readonly string  $mailSubject,
        private readonly string  $reportName,
        private readonly string  $periodLabel,
        private readonly string  $fileContents,
        private readonly string  $filename,
        private readonly string  $mimeType,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'models.scheduled-report',
            with: [
                'reportName'  => $this->reportName,
                'periodLabel' => $this->periodLabel,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->fileContents,
                $this->filename,
            )->withMime($this->mimeType),
        ];
    }
}
