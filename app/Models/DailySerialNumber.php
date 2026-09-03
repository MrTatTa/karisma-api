<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySerialNumber extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'date',
        'vehicle_type',
        'serial_start',
        'inputted_by',
        'inputted_at',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'inputted_at'  => 'datetime',
            'serial_start' => 'string',
        ];
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'inputted_by');
    }
}
