<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable()->after('reject_reason');
        });
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable()->after('reject_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropColumn('hidden_at');
        });
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('hidden_at');
        });
    }
};
