<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemDepartmentBudget;
use App\Models\ItemDepartmentBudgetTopup;
use App\Models\ProcurementItem;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestDetail;
use App\Models\PurchaseRequest;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use App\Services\BudgetService;
use App\Support\DemoMode;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    public function index()
    {
        $stats = [
            'pending_approvals'  => Transaction::where('status', 'pending')->where('trans_type', '!=', 'goods_out')->count(),
            'today_transactions' => Transaction::whereDate('created_at', today())->count(),
            'total_items'        => ProcurementItem::where('is_active', true)->count(),
        ];

        // User biasa cuma boleh lihat transaksi miliknya sendiri, bukan aktivitas
        // staff GA/admin lain -- selain itu juga bukan hal yang relevan buat mereka.
        $recentTransactions = Transaction::with(['user', 'department', 'details'])
            ->when(auth()->user()->role === 'user', fn($q) => $q->where('user_id', auth()->id()))
            ->latest()->limit(8)->get();

        // Chart data
        $monthlyData = Transaction::select(
                DB::raw('MONTH(created_at) as month'),
                'trans_type',
                DB::raw('COUNT(*) as total')
            )
            ->whereYear('created_at', now()->year)
            ->groupBy('month', 'trans_type')
            ->orderBy('month')
            ->get();

        $chartLabels = collect(range(1, 12))->map(fn($m) => now()->setMonth($m)->format('M'));
        $chartOut    = collect(range(1, 12))->map(fn($m) => $monthlyData->where('trans_type', 'goods_out')->where('month', $m)->sum('total'));
        $chartIn     = collect(range(1, 12))->map(fn($m) => $monthlyData->where('trans_type', 'goods_in')->where('month', $m)->sum('total'));
        $chartReturn = collect(range(1, 12))->map(fn($m) => $monthlyData->where('trans_type', 'goods_return')->where('month', $m)->sum('total'));

        // Buat role User: grafik transaksi gudang (Transaction) bukan data yang
        // relevan buat mereka (yang eksekusi selalu staff GA/admin) -- diganti
        // hitungan Permintaan Pengadaan yang dia ajukan sendiri per bulan.
        $chartMyRequests = null;
        if (auth()->user()->role === 'user') {
            $myMonthlyRequests = ProcurementRequest::select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as total')
                )
                ->where('user_id', auth()->id())
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->get();

            $chartMyRequests = collect(range(1, 12))
                ->map(fn($m) => $myMonthlyRequests->where('month', $m)->sum('total'));
        }

        // ===== DATA HARGA (SUPERADMIN ONLY) =====
        $priceStats = null;
        if (auth()->user()->isSuperAdmin()) {
            $priceStats = $this->getPriceStats();
        }

        // ===== BUDGET VS ACTUAL 12 BULAN =====
        // Admin/superadmin lihat rekap SEMUA departemen (dihitung pakai agregat SQL
        // langsung, bukan loop per-item x per-departemen x per-bulan -- department_id
        // pribadi mereka di data user cuma administratif, bukan representasi
        // "departemen yang relevan buat mereka"). Role lain tetap scoped ke
        // departemen sendiri seperti sebelumnya.
        $budgetHistory = [];
        if (auth()->user()->isAdmin()) {
            $totalDefaultBudget = (float) ItemDepartmentBudget::sum('default_budget');

            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);

                $topup = (float) ItemDepartmentBudgetTopup::where('year', $date->year)
                    ->where('month', $date->month)->sum('amount');

                $consumed = (float) ProcurementRequestDetail::join('procurement_requests', 'procurement_requests.id', '=', 'procurement_request_details.request_id')
                    ->join('procurement_items', 'procurement_items.id', '=', 'procurement_request_details.item_id')
                    ->where('procurement_requests.status', '!=', 'draft')
                    ->where('procurement_requests.status', '!=', 'rejected')
                    ->whereYear('procurement_requests.req_date', $date->year)
                    ->whereMonth('procurement_requests.req_date', $date->month)
                    // Join manual ke tabel mentah -- TIDAK ikut global scope
                    // ProcurementRequest, harus ditambal manual di sini.
                    ->when(DemoMode::range(), fn($q, $range) => $q->whereBetween('procurement_requests.req_date', $range))
                    ->select(DB::raw('COALESCE(SUM(procurement_request_details.qty * procurement_items.price), 0) as total'))
                    ->value('total');

                $budgetHistory[] = ['label' => $date->translatedFormat('M Y'), 'budget' => $totalDefaultBudget + $topup, 'consumed' => $consumed];
            }
        } else {
            $deptId = auth()->user()->department_id;
            if ($deptId) {
                $itemIds = ItemDepartmentBudget::where('department_id', $deptId)->pluck('procurement_item_id');
                if ($itemIds->isNotEmpty()) {
                    for ($i = 11; $i >= 0; $i--) {
                        $date = now()->subMonths($i);
                        $budget = 0;
                        $consumed = 0;
                        foreach ($itemIds as $itemId) {
                            $budget   += $this->budgetService->effectiveBudget($deptId, $itemId, $date->year, $date->month);
                            $consumed += $this->budgetService->consumed($deptId, $itemId, $date->year, $date->month);
                        }
                        $budgetHistory[] = ['label' => $date->translatedFormat('M Y'), 'budget' => $budget, 'consumed' => $consumed];
                    }
                }
            }
        }

        // Bulan berjalan = entri terakhir di $budgetHistory (loop di atas jalan dari 11 bulan lalu -> bulan ini).
        $currentMonthBudget   = $budgetHistory ? end($budgetHistory)['budget'] : 0;
        $currentMonthConsumed = $budgetHistory ? end($budgetHistory)['consumed'] : 0;

        // ===== NILAI BARANG DITERIMA PER DEPARTEMEN PER BULAN (GA & ADMIN) =====
        $receivedValueTable = null;
        if (auth()->user()->isGa() || auth()->user()->isAdmin()) {
            $receivedValueTable = $this->getReceivedValueByDepartmentMonth();
        }

        return view('dashboard.index', compact(
            'stats', 'recentTransactions',
            'chartLabels', 'chartOut', 'chartIn', 'chartReturn', 'chartMyRequests',
            'priceStats', 'budgetHistory', 'currentMonthBudget', 'currentMonthConsumed',
            'receivedValueTable'
        ));
    }

    /**
     * Nilai barang yang BENAR-BENAR DITERIMA (bukan sekadar diajukan) per
     * departemen per bulan, 12 bulan terakhir -- dipakai tabel khusus GA/Admin
     * di Dashboard. "Diterima" = PurchaseRequest sudah masuk tahap
     * distributing/completed (artinya sudah lewat receiving), nilainya
     * qty_needed x harga ATK saat ini (sama seperti perhitungan budget
     * konsumsi lain di aplikasi).
     */
    private function getReceivedValueByDepartmentMonth(): array
    {
        $months      = collect(range(11, 0))->map(fn($i) => now()->subMonths($i));
        $monthLabels = $months->map(fn($d) => $d->translatedFormat('M Y'))->all();

        $prs = PurchaseRequest::with('details.procurementItem', 'department')
            ->whereIn('status', ['distributing', 'completed'])
            ->get();

        $table = [];
        foreach ($prs as $pr) {
            $date  = $pr->received_at ?? $pr->created_at;
            $label = $date->translatedFormat('M Y');
            if (!in_array($label, $monthLabels)) continue;

            $deptName = $pr->department->name ?? '-';
            $value    = $pr->details->sum(fn($d) => $d->qty_needed * (float) ($d->procurementItem->price ?? 0));

            $table[$deptName][$label] = ($table[$deptName][$label] ?? 0) + $value;
        }
        ksort($table);

        $totals = collect($table)->map(fn($byMonth) => array_sum($byMonth))->sortDesc();

        return ['months' => $monthLabels, 'data' => $table, 'totals' => $totals];
    }

    private function getPriceStats(): array
    {
        $items = Item::with(['departmentStocks', 'memoStock'])->where('is_active', true)->get();

        // Total nilai inventory
        $totalInventoryValue = $items->sum(fn($item) => $item->getInventoryValue());

        // Nilai keluar barang bulan ini
        $thisMonth = now()->startOfMonth();
        $goodsOutValue = TransactionDetail::join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('items', 'transaction_details.item_id', '=', 'items.id')
            ->where('transactions.trans_type', 'goods_out')
            ->where('transactions.status', 'completed')
            ->where('transactions.trans_date', '>=', $thisMonth)
            ->when(DemoMode::range(), fn($q, $range) => $q->whereBetween('transactions.trans_date', $range))
            ->select(DB::raw('SUM(transaction_details.qty_approved * items.price) as total'))
            ->value('total') ?? 0;

        // Nilai pengembalian bulan ini
        $returnValue = TransactionDetail::join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('items', 'transaction_details.item_id', '=', 'items.id')
            ->where('transactions.trans_type', 'goods_return')
            ->where('transactions.status', 'completed')
            ->where('transactions.trans_date', '>=', $thisMonth)
            ->when(DemoMode::range(), fn($q, $range) => $q->whereBetween('transactions.trans_date', $range))
            ->select(DB::raw('SUM(transaction_details.qty_approved * items.price) as total'))
            ->value('total') ?? 0;

        // Nilai masuk barang bulan ini
        $goodsInValue = TransactionDetail::join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('items', 'transaction_details.item_id', '=', 'items.id')
            ->where('transactions.trans_type', 'goods_in')
            ->where('transactions.status', 'completed')
            ->where('transactions.trans_date', '>=', $thisMonth)
            ->when(DemoMode::range(), fn($q, $range) => $q->whereBetween('transactions.trans_date', $range))
            ->select(DB::raw('SUM(transaction_details.qty_approved * items.price) as total'))
            ->value('total') ?? 0;

        // Top 5 item nilai inventory tertinggi
        $topItems = $items->map(fn($item) => [
            'description' => $item->description,
            'item_code'   => $item->item_code,
            'qty'         => $item->getQtyOnHand(),
            'unit'        => $item->unit,
            'price'       => $item->price,
            'value'       => $item->getInventoryValue(),
        ])->sortByDesc('value')->take(5)->values();

        // Chart nilai transaksi per bulan (12 bulan)
        $valueChartData = collect(range(1, 12))->map(function ($month) {
            $out = TransactionDetail::join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->join('items', 'transaction_details.item_id', '=', 'items.id')
                ->where('transactions.trans_type', 'goods_out')
                ->where('transactions.status', 'completed')
                ->whereYear('transactions.trans_date', now()->year)
                ->whereMonth('transactions.trans_date', $month)
                ->when(DemoMode::range(), fn($q, $range) => $q->whereBetween('transactions.trans_date', $range))
                ->select(DB::raw('SUM(transaction_details.qty_approved * items.price) as total'))
                ->value('total') ?? 0;

            $in = TransactionDetail::join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->join('items', 'transaction_details.item_id', '=', 'items.id')
                ->where('transactions.trans_type', 'goods_in')
                ->where('transactions.status', 'completed')
                ->whereYear('transactions.trans_date', now()->year)
                ->whereMonth('transactions.trans_date', $month)
                ->when(DemoMode::range(), fn($q, $range) => $q->whereBetween('transactions.trans_date', $range))
                ->select(DB::raw('SUM(transaction_details.qty_approved * items.price) as total'))
                ->value('total') ?? 0;

            return ['out' => (float) $out, 'in' => (float) $in];
        });

        return [
            'total_inventory_value' => $totalInventoryValue,
            'goods_out_value'       => $goodsOutValue,
            'goods_in_value'        => $goodsInValue,
            'return_value'          => $returnValue,
            'top_items'             => $topItems,
            'value_chart_out'       => $valueChartData->pluck('out')->values(),
            'value_chart_in'        => $valueChartData->pluck('in')->values(),
            'items_with_price'      => $items->where('price', '>', 0)->count(),
            'items_no_price'        => $items->where('price', 0)->count(),
        ];
    }
}
