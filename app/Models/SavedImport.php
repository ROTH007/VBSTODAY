<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavedImport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'label',
        'company',
        'status',
        'match_count',
        'not_match_count',
        'bill_count',
        'other_infrastructure_count',
        'not_in_builder_count',
        'created_by',
    ];

    public function unmatchedReasons(): HasMany
    {
        return $this->hasMany(SavedImportUnmatchedReason::class);
    }
}
