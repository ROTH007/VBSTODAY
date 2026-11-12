<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verified_infrastructures', function (Blueprint $table) {
            $table->id();
            $table->string('work_order');
            $table->string('verified_infrastructure');
            $table->timestamps();

            $table->unique('work_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verified_infrastructures');
    }
};
