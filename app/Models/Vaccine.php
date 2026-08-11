<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vaccine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_age_months',
        'max_age_months',
        'description',
    ];

    public function schedules()
    {
        return $this->hasMany(VaccineSchedule::class);
    }

    public function childVaccinations()
    {
        return $this->hasMany(ChildVaccination::class);
    }
}
