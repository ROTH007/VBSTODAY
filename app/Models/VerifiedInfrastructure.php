<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifiedInfrastructure extends Model
{
    protected $fillable = [
        'work_order',
        'verified_infrastructure',
    ];
}
