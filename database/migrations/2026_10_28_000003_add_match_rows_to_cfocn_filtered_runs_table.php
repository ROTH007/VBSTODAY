<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfocn_filtered_runs', function (Blueprint $table) {
            $table->json('match_rows')->nullable()->after('not_match_rows');
        });
    }

    public function down(): void
    {
        Schema::table('cfocn_filtered_runs', function (Blueprint $table) {
            $table->dropColumn('match_rows');
        });
    }
};
