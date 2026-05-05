<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ParentModel;
use App\Models\Growth;
use App\Models\Appointment;

class Child extends Model
{
    protected $guarded=[];

    public function parent(){
        return $this->belongsTo(ParentModel::class,'parent_id');
    }

    public function growth(){
        return $this->hasMany(Growth::class);
    }

    public function appointments(){
        return $this->hasMany(Appointment::class);
    }

}
