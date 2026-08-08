<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class VaccineSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'vaccine_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    public function vaccine()
    {
        return $this->belongsTo(Vaccine::class);
    }
}
