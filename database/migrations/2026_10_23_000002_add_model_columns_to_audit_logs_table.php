<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * audit_logs already exists (created for the /app SPA's import feature:
 * import_id, user_id, action, details, created_at). The new saved-imports
 * CRUD feature reuses the same table as one shared audit trail instead of a
 * second table, so this just adds the columns it needs. `action` stays a
 * plain string column (already was) rather than a DB enum, validated as
 * create/update/delete in PHP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('model')->nullable()->after('user_id');
            $table->unsignedBigInteger('model_id')->nullable()->after('model');
            $table->json('changes')->nullable()->after('model_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['model', 'model_id', 'changes']);
        });
    }
};
