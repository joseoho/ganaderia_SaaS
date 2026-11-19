<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al operador de la plataforma: hay un comprobante nuevo por revisar.
 *
 * Se manda por correo porque el operador no tiene ganadería y las
 * notificaciones internas de la app cuelgan de una finca. Con MAIL_MAILER=log
 * el mensaje queda en storage/logs/laravel.log, que sirve para probar.
 */
class AvisoParaOperador extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $texto,
        public readonly string $asunto,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->asunto);
    }

    public function content(): Content
    {
        return new Content(htmlString: nl2br(e($this->texto)));
    }
}
