<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsRollup extends Model
{
    protected $fillable = ['date','metric','dimension','value'];

    protected $casts = ['date' => 'date'];
}
