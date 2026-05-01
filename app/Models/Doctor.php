<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Department;
use App\Models\ParentModel;
use App\Models\Appointment;
use App\Models\DoctorAvailability;

class Doctor extends Model
{
    public function department(){
        return $this->belongsTo(Department::class);
    }
    public function parents(){
        return $this->belongsToMany(ParentModel::class,'favorite');

    }
    public function appointments(){
        return $this->hasMany(Appointment::class);

    }
    public function availability(){
        return $this->hasMany(DoctorAvailability::class);

    }


}
