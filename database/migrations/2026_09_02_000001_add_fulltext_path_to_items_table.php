<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Full text can be supplied two ways (decision 2026-09-02):
     *   - fulltext_url  : an external link the officer pastes (already exists; CSV import fills this from `identifier`)
     *   - fulltext_path : a PDF uploaded through the "เพิ่มรายการ" wizard, stored on the `public` disk under fulltext/
     * Item::getFulltextAttribute() prefers the URL, else resolves the stored path.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('fulltext_path')->nullable()->after('fulltext_url');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('fulltext_path');
        });
    }
};
