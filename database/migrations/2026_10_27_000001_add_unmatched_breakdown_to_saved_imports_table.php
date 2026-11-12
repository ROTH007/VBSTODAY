<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_imports', function (Blueprint $table) {
            $table->unsignedInteger('other_infrastructure_count')->default(0)->after('bill_count');
            $table->unsignedInteger('not_in_builder_count')->default(0)->after('other_infrastructure_count');
        });
    }

    public function down(): void
    {
        Schema::table('saved_imports', function (Blueprint $table) {
            $table->dropColumn(['other_infrastructure_count', 'not_in_builder_count']);
        });
    }
};
