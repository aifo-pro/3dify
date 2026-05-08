<?php

namespace App\Mail;

use App\Models\NewsletterBlast;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterBlastMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterBlast $blast, public NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->blast->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter-blast',
            with: [
                'blast' => $this->blast,
                'subscriber' => $this->subscriber,
                'unsubscribeUrl' => route('newsletter.unsubscribe', ['token' => $this->subscriber->unsubscribe_token]),
            ],
        );
    }
}
