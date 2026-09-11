<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseInvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $generalInfo;

    public function __construct($order, $generalInfo)
    {
        $this->order       = $order;
        $this->generalInfo = $generalInfo;
    }

    public function build()
    {
        $subject = 'Purchase Invoice #' . ($this->order->code ?? 'N/A')
            . ' — ' . ($this->generalInfo->company_name ?? config('app.name'));

        return $this->subject($subject)
                    ->view('invoice.purchase.purchase_order_invoice')
                    ->with([
                        'order'       => $this->order,
                        'generalInfo' => $this->generalInfo,
                        'isPublic'    => true,
                    ]);
    }
}
