<?php

namespace App\Http\Controllers;

use App\Models\ItemCodePrefix;
use App\Models\ItemSuggestion;
use App\Models\ProcurementCategory;
use App\Models\ProcurementItem;
use App\Models\Uom;
use App\Models\User;
use App\Notifications\ItemSuggestedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemSuggestionController extends Controller
{
    /** Daftar usulan milik user sendiri (semua role requester). */
    public function index()
    {
        $suggestions = ItemSuggestion::with(['uom', 'procurementItem', 'reviewer'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('procurement.item-suggestions.index', compact('suggestions'));
    }

    public function create()
    {
        $uoms = Uom::where('is_active', true)->orderBy('code')->get();
        return view('procurement.item-suggestions.create', compact('uoms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:200',
            'uom_id' => 'required|exists:uoms,id',
            'photo'  => 'nullable|image|max:8192',
            'notes'  => 'nullable|string|max:500',
        ]);

        $data = [
            'user_id' => auth()->id(),
            'name'    => $request->name,
            'uom_id'  => $request->uom_id,
            'notes'   => $request->notes,
            'status'  => 'pending',
        ];

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->storePhoto($request->file('photo'));
        }

        $suggestion = ItemSuggestion::create($data);

        $gaUsers = User::where('role', 'ga')->where('is_active', true)->get();
        $this->safeNotify($gaUsers, new ItemSuggestedNotification($suggestion));

        return redirect()->route('procurement.item-suggestions.index')
            ->with('success', 'Usulan item baru terkirim, menunggu review GA.');
    }

    /** Antrian review GA (role:ga,superadmin). */
    public function reviewIndex()
    {
        $pending = ItemSuggestion::with(['user.department', 'uom'])
            ->where('status', 'pending')->latest()->get();

        $history = ItemSuggestion::with(['user.department', 'uom', 'reviewer', 'procurementItem'])
            ->whereIn('status', ['approved', 'rejected'])->latest()->limit(50)->get();

        $categories = ProcurementCategory::where('is_active', true)->orderBy('name')->get();
        $prefixes   = ItemCodePrefix::where('is_active', true)->orderBy('code')->get();

        return view('procurement.item-suggestions.review', compact('pending', 'history', 'categories', 'prefixes'));
    }

    public function approve(Request $request, ItemSuggestion $itemSuggestion)
    {
        if ($itemSuggestion->status !== 'pending') {
            return back()->with('error', 'Usulan ini sudah direview.');
        }

        $request->validate([
            'prefix'      => 'required|string|exists:item_code_prefixes,code',
            'category_id' => 'nullable|exists:procurement_categories,id',
            'price'       => 'required|numeric|min:0',
        ]);

        $item = ProcurementItem::create([
            'item_code'         => ProcurementItem::generateItemCode($request->prefix),
            'name'              => $itemSuggestion->name,
            'uom_id'            => $itemSuggestion->uom_id,
            'category_id'       => $request->category_id,
            'price'             => $request->price,
            'photo'             => $itemSuggestion->photo,
            'is_active'         => true,
            'is_user_suggested' => true,
        ]);

        $itemSuggestion->update([
            'status'              => 'approved',
            'reviewed_by'         => auth()->id(),
            'reviewed_at'         => now(),
            'procurement_item_id' => $item->id,
        ]);

        return back()->with('success', "Usulan \"{$itemSuggestion->name}\" disetujui, item {$item->item_code} sudah masuk katalog.");
    }

    public function reject(Request $request, ItemSuggestion $itemSuggestion)
    {
        if ($itemSuggestion->status !== 'pending') {
            return back()->with('error', 'Usulan ini sudah direview.');
        }

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $itemSuggestion->update([
            'status'            => 'rejected',
            'reviewed_by'       => auth()->id(),
            'reviewed_at'       => now(),
            'rejection_reason'  => $request->rejection_reason,
        ]);

        return back()->with('success', "Usulan \"{$itemSuggestion->name}\" ditolak.");
    }

    private function storePhoto(\Illuminate\Http\UploadedFile $file): string
    {
        [$width, $height] = getimagesize($file->getRealPath());

        $source = match ($file->getMimeType()) {
            'image/png'  => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            'image/gif'  => imagecreatefromgif($file->getRealPath()),
            default      => imagecreatefromjpeg($file->getRealPath()),
        };

        $side = min($width, $height);
        $srcX = (int) (($width - $side) / 2);
        $srcY = (int) (($height - $side) / 2);

        $target = 600;
        $dest = imagecreatetruecolor($target, $target);
        $white = imagecolorallocate($dest, 255, 255, 255);
        imagefill($dest, 0, 0, $white);
        imagecopyresampled($dest, $source, 0, 0, $srcX, $srcY, $target, $target, $side, $side);

        $filename = 'photos/procurement/' . uniqid('suggest_', true) . '.jpg';
        $tmpPath = tempnam(sys_get_temp_dir(), 'sqphoto');
        imagejpeg($dest, $tmpPath, 85);

        imagedestroy($source);
        imagedestroy($dest);

        Storage::disk('public')->put($filename, file_get_contents($tmpPath));
        unlink($tmpPath);

        return $filename;
    }
}
