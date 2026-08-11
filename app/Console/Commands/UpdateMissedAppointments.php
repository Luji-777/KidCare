<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;

class UpdateMissedAppointments extends Command
{

    protected $signature = 'appointments:update-missed';
    protected $description = 'Automatically update expired confirmed appointments to missed status';

    public function handle()
    {

        $affectedRows = Appointment::where('status', 'confirmed')
            ->where(function ($query) {
                $query->where('date', '<', today());
            })
            ->update(['status' => 'missed']);

        $this->info("Successfully updated {$affectedRows} appointments to missed status.");
    }
}
