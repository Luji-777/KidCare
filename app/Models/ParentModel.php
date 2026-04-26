<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Child;
use App\Models\Notification;
use App\Models\Doctor;



class ParentModel extends Model
{
    protected $guarded=[];

    public function children(){
        return $this->hasMany(Child::class);
    }

    public function notification(){
        return $this->hasMany(Notification::class);
    }

    public function doctors(){
        return $this->belongsToMany(Doctor::class,'favorite');

    }

}
