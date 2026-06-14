<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class PurchaseConfirmationMail extends Mailable
{
    public function __construct(
        public readonly Order $order,
        private readonly string $subjectLine,
        private readonly string $htmlBody,
    ) {}

    public function build(): static
    {
        return $this->subject($this->subjectLine)
            ->html($this->htmlBody);
    }
}

