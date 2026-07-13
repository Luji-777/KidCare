<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ParentModel;

class Notification extends Model
{
    protected $guarded=[];

    public function parent(){

        return $this->belongsTo(ParentModel::class);
    }
}
