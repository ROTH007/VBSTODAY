<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedImportUnmatchedReason extends Model
{
    protected $fillable = [
        'saved_import_id',
        'work_order',
        'reason',
    ];

    public function savedImport(): BelongsTo
    {
        return $this->belongsTo(SavedImport::class);
    }
}
