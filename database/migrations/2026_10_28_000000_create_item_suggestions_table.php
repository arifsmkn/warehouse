<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('name', 200);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('procurement_item_id')->nullable()->constrained('procurement_items');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_suggestions');
    }
};
