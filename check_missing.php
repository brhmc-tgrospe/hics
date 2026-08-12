<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/supplies', 'POST', [
    'category' => 'officesup',
    'article' => 'Test',
    'description' => 'Test Desc',
    'unit_value' => 0,
    'balance_per_card' => 10,
    'on_hand_per_count' => 10,
    'division_id' => 1,
    'area_id' => 1
]);

$validator = Illuminate\Support\Facades\Validator::make($request->all(), [
    'unit_value' => 'required|numeric|gt:0'
]);

if ($validator->fails()) {
    echo "Validation failed successfully!\n";
    print_r($validator->errors()->all());
} else {
    echo "Validation passed (this should not happen).\n";
}
