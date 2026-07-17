<?php

$user = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'Admin');
})->first();

auth()->login($user);

$action = app(\App\Domain\Users\Actions\GetUsersAction::class);

echo "Admin Division: " . $user->division_id . "\n";
echo "Total without filter: " . $action->execute([])->total() . "\n";
echo "Total with filter (true string): " . $action->execute(['division_only' => 'true'])->total() . "\n";
echo "Total with filter (true bool): " . $action->execute(['division_only' => true])->total() . "\n";
echo "Total with filter (false string): " . $action->execute(['division_only' => 'false'])->total() . "\n";
