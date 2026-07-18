<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event)
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'division_id' => $event->user->division_id,
                'area_id' => $event->user->area_id,
            ])
            ->log('Login');
    }
}
