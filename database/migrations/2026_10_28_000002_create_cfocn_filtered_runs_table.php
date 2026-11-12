<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfocn_filtered_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('total_cfocn_work_orders');
            $table->unsignedInteger('matched');
            $table->unsignedInteger('not_matched');
            $table->boolean('matched_plus_not_matched_equals_total');
            $table->json('not_match_rows');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfocn_filtered_runs');
    }
};
