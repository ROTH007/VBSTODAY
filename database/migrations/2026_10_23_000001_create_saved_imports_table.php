<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_imports', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->string('company');
            $table->string('status')->nullable();
            $table->unsignedInteger('match_count')->default(0);
            $table->unsignedInteger('not_match_count')->default(0);
            $table->unsignedInteger('bill_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_imports');
    }
};
