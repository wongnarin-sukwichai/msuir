<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // หัวเรื่อง — จาก subject1..5 เก็บค่าเป็น string ตรง ๆ (ไม่มีตาราง subjects)
        Schema::create('item_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('value');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('value');                    // subject-browse / facet
            $table->unique(['item_id', 'value']);      // กันหัวเรื่องซ้ำในรายการเดียว
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_subject');
    }
};
