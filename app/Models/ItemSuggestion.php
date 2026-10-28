<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSuggestion extends Model
{
    protected $fillable = [
        'user_id', 'name', 'uom_id', 'photo', 'notes',
        'status', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'procurement_item_id',
    ];
    protected $casts = ['reviewed_at' => 'datetime'];

    public function user()           { return $this->belongsTo(User::class); }
    public function uom()            { return $this->belongsTo(Uom::class); }
    public function reviewer()       { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function procurementItem(){ return $this->belongsTo(ProcurementItem::class); }
}
