<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('status_filter')->nullable();
            $table->string('builder_filename');
            $table->string('cfo_filename');
            $table->unsignedInteger('match_count')->default(0);
            $table->unsignedInteger('not_match_count')->default(0);
            $table->unsignedInteger('bill_count')->default(0);
            $table->string('status')->default('processing');
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
