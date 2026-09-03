<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vehicle_type',
        'amount',
        'effective_date',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
