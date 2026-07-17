<?php

$user = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'Admin');
})->first();

auth()->login($user);

$action = app(\App\Domain\Users\Actions\GetUsersAction::class);

echo "Total without filter keys (default request): " . $action->execute([
    'search' => null,
    'per_page' => null,
    'division_only' => null
])->total() . "\n";
