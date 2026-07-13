<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Appointment_additions;

class Appointment_additionsSeeder extends Seeder
{
    public function run(): void
    {

        $appointments = Appointment::all();

        $fakeAdditions = [
            ['item_name' => 'شاش طبي ومعقم جروح', 'price' => 15.00],
            ['item_name' => 'تحليل دم سريع بالعيادة', 'price' => 45.00],
            ['item_name' => 'مستلزمات تجبير كسر خفيف', 'price' => 70.00],
            ['item_name' => 'بخاخ موسع قصبات إسعافي', 'price' => 25.00],
            ['item_name' => 'فحص نظر بجهاز مخصص', 'price' => 30.00],
        ];

        foreach ($appointments as $appointment) {

            if (rand(1, 10) <= 7) {

                $numberOfItems = rand(1, 3);
                $shuffledAdditions = collect($fakeAdditions)->shuffle();

                for ($i = 0; $i < $numberOfItems; $i++) {
                    Appointment_additions::create([
                        'appointment_id' => $appointment->id,
                        'item_name'      => $shuffledAdditions[$i]['item_name'],
                        'price'          => $shuffledAdditions[$i]['price'],
                    ]);
                }
            }
        }
    }
}
