<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Doctor;
use App\Models\Child;
use App\Models\medicalRecord;

class Appointment extends Model
{
    protected $guarded = [];
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function record()
    {
        return $this->hasOne(medicalRecord::class);
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    public function additions()
    {
        return $this->hasMany(Appointment_additions::class);
    }

    public function getAdditionsTotalAttribute()
    {
        return $this->additions()->sum('price');
    }

    public function getTotalPaidAttribute()
    {
        return $this->transactions()->where('status', 'succeeded')->sum('amount');
    }
}
