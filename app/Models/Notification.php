<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ParentModel;

class Notification extends Model
{
    public function parent(){

        return $this->belongsTo(ParentModel::class);
    }
}
