<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Doctor;

class DoctorAvailability extends Model
{
    protected $guarded = [];
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
