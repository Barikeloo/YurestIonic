<?php

declare(strict_types=1);

namespace App\Mail;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class TaxReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $legalName,
        private readonly string $businessName,
        private readonly string $taxId,
        private readonly string $period,
        private readonly array  $rates,
        private readonly int    $totalBase,
        private readonly int    $totalTax,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Borrador Modelo 303 — {$this->period}",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'models.empty',
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.modelo303', [
            'legalName'    => $this->legalName,
            'businessName' => $this->businessName,
            'taxId'        => $this->taxId,
            'period'       => $this->period,
            'rates'        => $this->rates,
            'totalBase'    => $this->totalBase,
            'totalTax'     => $this->totalTax,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return [
            Attachment::fromData(
                fn() => $pdf->output(),
                "modelo303-{$this->period}.pdf",
            ),
        ];
    }
}
