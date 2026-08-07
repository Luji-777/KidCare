<?php

namespace App\Models;
use App\Models\Doctor;

use Illuminate\Database\Eloquent\Model;

class DoctorNotification extends Model
{
    protected $guarded = [];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
