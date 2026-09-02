<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When an admin sends an item back from the review queue (status → action_required)
     * the reason is stored here so the owner sees what to fix. Cleared on approve.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->text('review_note')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('review_note');
        });
    }
};
