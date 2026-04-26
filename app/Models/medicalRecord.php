<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Appointment;
use App\Models\Medication;


class medicalRecord extends Model
{
    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }

    public function medications(){
        return $this->hasMany(Medication::class);
    }
}
