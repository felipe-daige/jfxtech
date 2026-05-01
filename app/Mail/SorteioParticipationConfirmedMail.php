<?php

namespace App\Mail;

use App\Models\SorteioParticipante;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SorteioParticipationConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SorteioParticipante $participacao,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu número do sorteio JFXTECH: '.$this->participacao->numeroFormatado(),
        );
    }

    public function content(): Content
    {
        $this->participacao->loadMissing(['sorteio', 'user']);

        return new Content(
            view: 'emails.sorteios.participation-confirmed',
            with: [
                'participacao' => $this->participacao,
                'sorteio' => $this->participacao->sorteio,
                'user' => $this->participacao->user,
                'acompanharUrl' => route('site.sorteio.acompanhar', $this->participacao->sorteio),
            ],
        );
    }
}
