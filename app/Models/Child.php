<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ParentModel;
use App\Models\Growth;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

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

    public function childVaccinations()
    {
        return $this->hasMany(ChildVaccination::class);
    }
    public function vaccines()
    {
        return $this->belongsToMany(Vaccine::class, 'child_vaccinations')
            ->withPivot('given_date', 'notes', 'receptionist_id')
            ->withTimestamps();
    }


    public function getAgeInMonthsAttribute()
    {
        if (!$this->birth_date) {
            return 0;
        }

        return Carbon::parse($this->birth_date)->diffInMonths(now());
    }
    protected $casts = [
        'birth_date' => 'date',
    ];


    /**
     * فحص هل الطفل ما زال ضمن السن المسموح للعيادة (أصغر من أو يساوي 6 سنوات)
     */
    public function isEligibleForClinic(): bool
    {
        // 6 سنوات × 12 شهر = 72 شهراً
        return $this->age_in_months <= 72;
    }

    /**
     * Scope لجلب الأطفال المؤهلين فقط للعيادة مباشرة من قاعدة البيانات
     */
    public function scopeEligibleForClinic($query)
    {
        return $query->where('birth_date', '>=', now()->subYears(6));
    }
}
