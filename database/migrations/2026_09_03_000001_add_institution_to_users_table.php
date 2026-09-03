<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * External members (is_msu_member = false, e.g. staff from another school /
     * university) have no MSU faculty — their affiliation is free text here.
     * MSU members keep using department_id (FK → deps).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('institution')->nullable()->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('institution');
        });
    }
};
