<?php

namespace App\Notifications;

use App\Models\ProcurementRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SectionApprovalNeededNotification extends Notification
{
    public function __construct(private ProcurementRequest $procurementRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'whatsapp'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pr = $this->procurementRequest;
        $pr->loadMissing('details.item.uom');

        $mail = (new MailMessage)
            ->subject("Persetujuan Diperlukan: Permintaan Kebutuhan GA {$pr->req_no}")
            ->greeting("Halo {$notifiable->name},")
            ->line("{$pr->user->name} dari departemen {$pr->department?->name} mengajukan permintaan kebutuhan GA {$pr->req_no}.")
            ->line("Tujuan: {$pr->purpose}")
            ->line('**Rincian item:**');

        $total = 0;
        foreach ($pr->details as $d) {
            $subtotal = $d->qty * ($d->item->price ?? 0);
            $total += $subtotal;
            $mail->line(sprintf(
                '- %s (%s): %s %s x Rp %s = Rp %s',
                $d->item->name,
                $d->item->item_code,
                rtrim(rtrim(number_format($d->qty, 2, ',', '.'), '0'), ','),
                $d->uom->code ?? $d->item->uom?->code,
                number_format($d->item->price ?? 0, 0, ',', '.'),
                number_format($subtotal, 0, ',', '.'),
            ));
        }

        $mail->line("**Total: Rp " . number_format($total, 0, ',', '.') . '**')
            ->action('Review Permintaan', route('procurement.requests.show', $pr))
            ->line('Mohon segera ditinjau untuk approval.');

        return $mail;
    }

    public function toWhatsapp(object $notifiable): string
    {
        $pr = $this->procurementRequest;

        return "Halo {$notifiable->name}, ada permintaan kebutuhan GA baru ({$pr->req_no}) dari {$pr->user->name} ({$pr->department?->name}) yang menunggu persetujuan Anda. Cek di aplikasi warehouse.";
    }
}
