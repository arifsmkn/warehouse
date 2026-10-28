<?php

namespace App\Notifications;

use App\Models\ItemSuggestion;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ItemSuggestedNotification extends Notification
{
    public function __construct(private ItemSuggestion $itemSuggestion) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'whatsapp'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->itemSuggestion;

        return (new MailMessage)
            ->subject("Usulan Item Baru: {$s->name}")
            ->greeting("Halo {$notifiable->name},")
            ->line("{$s->user->name} ({$s->user->department?->name}) mengusulkan item baru: \"{$s->name}\".")
            ->action('Buka Daftar Usulan Item', route('ga.item-suggestions.index'))
            ->line('Silakan direview, lengkapi harga & kategorinya kalau disetujui.');
    }

    public function toWhatsapp(object $notifiable): string
    {
        $s = $this->itemSuggestion;

        return "Halo {$notifiable->name}, {$s->user->name} ({$s->user->department?->name}) mengusulkan item baru \"{$s->name}\" -- silakan direview di dashboard GA.";
    }
}
