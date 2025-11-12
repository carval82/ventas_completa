<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\EmailBuzon;

class AcuseReciboMail extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $datosFactura;
    public $empresa;

    /**
     * Create a new message instance.
     */
    public function __construct(EmailBuzon $email, array $datosFactura, $empresa)
    {
        $this->email = $email;
        $this->datosFactura = $datosFactura;
        $this->empresa = $empresa;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $numeroFactura = $this->datosFactura['numero_factura'] ?? 'N/A';
        
        return new Envelope(
            subject: "Acuse de Recibo - Factura Electrónica {$numeroFactura}",
            from: $this->email->cuenta_email,
            replyTo: $this->email->cuenta_email
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.acuse-recibo',
            with: [
                'email' => $this->email,
                'datosFactura' => $this->datosFactura,
                'empresa' => $this->empresa,
                'fechaAcuse' => now()->format('d/m/Y H:i:s')
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
