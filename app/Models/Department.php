<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Doctor;

class Department extends Model
{
    public function doctors(){
        return $this->hasMany(Doctor::class);
    }
}
