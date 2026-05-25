<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParentModel;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ParentSeeder extends Seeder
{

    public function run(): void
    {
        ParentModel::factory()->count(10)->create();
    }
}
