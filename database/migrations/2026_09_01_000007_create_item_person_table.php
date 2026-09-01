<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ผู้แต่ง — creator (จาก column creator) + contributor1..8
        // เก็บชื่อเป็น string ตรง ๆ ตามที่เจ้าหน้าที่กรอกใน CSV (ไม่มีตาราง people)
        Schema::create('item_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('name');
            $table->enum('role', ['creator', 'contributor']);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('name');                          // "ผลงานทั้งหมดของผู้แต่งคนนี้"
            $table->unique(['item_id', 'name', 'role']);     // กันชื่อซ้ำในรายการเดียว
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_person');
    }
};
