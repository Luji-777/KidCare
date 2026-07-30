<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment_additions extends Model
{   
     protected $guarded = [];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
