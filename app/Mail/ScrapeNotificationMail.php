<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScrapeNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, array<int, array{href: string, text: string, code?: string, company_name?: string}>>
     */
    public function __construct(
        public array $matched
    ) {}

    public function envelope(): Envelope
    {
        $subject = 'スクレイプ通知';
        foreach ($this->matched as $posts) {
            foreach ($posts as $post) {
                $subject = $post['text'];
                break 2;
            }
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scrape-notification',
        );
    }
}
