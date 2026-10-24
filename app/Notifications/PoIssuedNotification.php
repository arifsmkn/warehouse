<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PoIssuedNotification extends Notification
{
    public function __construct(private PurchaseRequest $purchaseRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'whatsapp'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pr = $this->purchaseRequest;

        return (new MailMessage)
            ->subject("PO Terbit: {$pr->pr_no} ({$pr->qad_po_no}) Siap Diterima")
            ->greeting("Halo {$notifiable->name},")
            ->line("QAD sudah menerbitkan PO untuk permintaan {$pr->pr_no} (departemen {$pr->department?->name}).")
            ->line("No. Requisition QAD: {$pr->qad_req_no}")
            ->line("No. PO QAD: {$pr->qad_po_no}")
            ->action('Buka Halaman Terima Barang', route('ga.receiving.create', $pr))
            ->line('Silakan diproses penerimaannya begitu barang fisik sudah sampai.');
    }

    public function toWhatsapp(object $notifiable): string
    {
        $pr = $this->purchaseRequest;

        return "Halo {$notifiable->name}, PO {$pr->qad_po_no} untuk permintaan {$pr->pr_no} ({$pr->department?->name}) sudah terbit di QAD dan siap diterima.";
    }
}
