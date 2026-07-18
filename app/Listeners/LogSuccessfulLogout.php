<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event)
    {
        if ($event->user) {
            activity('auth')
                ->causedBy($event->user)
                ->withProperties([
                    'division_id' => $event->user->division_id,
                    'area_id' => $event->user->area_id,
                ])
                ->log('Logout');
        }
    }
}
