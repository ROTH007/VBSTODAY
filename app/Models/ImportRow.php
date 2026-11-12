<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_id',
        'match_type',
        'row_data',
        'created_at',
    ];

    protected $casts = [
        'row_data' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
