<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('deps')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('title');
            $table->text('description')->nullable();

            // ปีตามต้นฉบับ: แถวไทยเป็น พ.ศ. (2541–2569), แถวอังกฤษเป็น ค.ศ. — ไม่แปลง
            $table->unsignedSmallInteger('year_issued')->nullable();

            $table->enum('language', ['tha', 'eng'])->default('tha');
            $table->string('rights')->nullable();
            $table->string('format')->default('pdf');
            $table->string('degree')->nullable();       // ว่างทุกแถวในชุดข้อมูลปัจจุบัน เก็บไว้เผื่ออนาคต
            $table->text('fulltext_url')->nullable();

            $table->enum('status', ['pending', 'approved', 'action_required'])->default('approved');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
