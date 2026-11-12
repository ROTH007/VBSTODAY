<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfocnFilteredRun extends Model
{
    protected $fillable = [
        'total_cfocn_work_orders',
        'matched',
        'not_matched',
        'matched_plus_not_matched_equals_total',
        'not_match_rows',
        'match_rows',
    ];

    protected $casts = [
        'matched_plus_not_matched_equals_total' => 'boolean',
        'not_match_rows' => 'array',
        'match_rows' => 'array',
    ];
}
