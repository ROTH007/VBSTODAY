<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_import_unmatched_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_import_id')->constrained()->cascadeOnDelete();
            $table->string('work_order');
            $table->string('reason');
            $table->timestamps();

            $table->index(['saved_import_id', 'work_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_import_unmatched_reasons');
    }
};
