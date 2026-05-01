<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Child;
use App\Models\Notification;
use App\Models\Doctor;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;



class ParentModel extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
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
