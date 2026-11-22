<?php

namespace App\Mail;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportTicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public SupportTicketReply $reply,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on ticket '.$this->ticket->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support.ticket-reply',
        );
    }
}
