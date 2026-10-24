<?php

namespace App\Console\Commands;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PoIssuedNotification;
use App\Services\QadSoapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SyncQadPoNumbers extends Command
{
    protected $signature = 'qad:sync-po-numbers';

    protected $description = 'Cek PO QAD (via WSA SDI_getPRtoPO_) untuk PR yang sudah terkirim tapi qad_po_no-nya masih kosong, lalu isi otomatis kalau sudah ada.';

    public function handle(QadSoapService $qad): int
    {
        if (!$qad->isWsaConfigured()) {
            $this->warn('QAD WSA belum dikonfigurasi (QAD_WSA_URL/QAD_WSA_NAMESPACE) — dilewati.');
            return self::SUCCESS;
        }

        $pending = PurchaseRequest::query()
            ->whereIn('status', ['sent_to_qad', 'director_confirmed'])
            ->whereNotNull('qad_req_no')
            ->whereNull('qad_po_no')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Tidak ada PR yang perlu dicek.');
            return self::SUCCESS;
        }

        $updated = 0;

        foreach ($pending as $pr) {
            $result = $qad->findPurchaseOrderByRequisition($pr->qad_req_no);

            if (!$result || empty($result['po_no'])) {
                continue;
            }

            $pr->update(['qad_po_no' => $result['po_no']]);
            $updated++;

            Log::info('QAD PO auto-detected', [
                'purchase_request_id' => $pr->id,
                'qad_req_no' => $pr->qad_req_no,
                'qad_po_no' => $result['po_no'],
            ]);

            // GA yang bakal proses receiving selanjutnya -- gagal kirim
            // notifikasi (mis. mailbox penuh) tidak boleh menggagalkan sync
            // command ini sendiri, cuma dicatat di log.
            try {
                $gaUsers = User::where('role', 'ga')->where('is_active', true)->get();
                Notification::send($gaUsers, new PoIssuedNotification($pr->fresh()));
            } catch (\Throwable $e) {
                Log::error('Gagal kirim notifikasi PoIssuedNotification: ' . $e->getMessage());
            }

            $this->info("PR #{$pr->id} ({$pr->qad_req_no}) -> PO {$result['po_no']}");
        }

        $this->info("Selesai. Dicek: {$pending->count()}, di-update: {$updated}.");

        return self::SUCCESS;
    }
}
