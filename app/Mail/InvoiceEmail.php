<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $company;
    public $qrData;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\ProductOrder $order
     * @param array $company
     * @param string $qrData
     * @return void
     */
    public function __construct($order, array $company, $qrData)
    {
        $this->order = $order;
        $this->company = $company;
        $this->qrData = $qrData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Invoice #' . $this->order->order_code)
            ->view('backend.mail.invoiceEmail');
    }
}
