<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ParentModel;
use App\Models\Growth;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Child extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function growth()
    {
        return $this->hasMany(Growth::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    protected function image(): Attribute
    {
        return Attribute::make(
            get: function ($value) {

                if ($value && file_exists(public_path($value))) {
                    return asset($value);
                }

                if ($this->gender === 'male') {
                    return asset('images/boy.png');
                }

                return asset('images/girl.png');
            },
        );
    }
}
