<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'tariff_amount',
        'date',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'date'        => 'date',
        ];
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
