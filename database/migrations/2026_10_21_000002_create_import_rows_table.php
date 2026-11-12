<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
            $table->string('match_type');
            // Plain text, not a native MySQL JSON column: MySQL's JSON type
            // does not preserve object key order on storage/retrieval, which
            // would scramble the row's column order for the Excel export.
            $table->longText('row_data');
            $table->timestamp('created_at')->nullable();

            $table->index(['import_id', 'match_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
