<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Department;
use App\Models\ParentModel;
use App\Models\Appointment;
use App\Models\DoctorAvailability;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Doctor extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $guarded = [];
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'favorite');
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    public function availability()
    {
        return $this->hasMany(DoctorAvailability::class);
    }
}
