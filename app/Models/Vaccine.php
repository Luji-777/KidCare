<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vaccine extends Model
{
    public function children()
{
    return $this->belongsToMany(
        Child::class,
        'child_vaccines'
    );
}
}
