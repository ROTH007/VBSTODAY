<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    protected $fillable = [
        'company',
        'status_filter',
        'builder_filename',
        'cfo_filename',
        'match_count',
        'not_match_count',
        'bill_count',
        'status',
        'error_message',
        'created_by',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
