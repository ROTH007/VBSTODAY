<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_id',
        'user_id',
        'action',
        'details',
        'model',
        'model_id',
        'changes',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'changes' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
