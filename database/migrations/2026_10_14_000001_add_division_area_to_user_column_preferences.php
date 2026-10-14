<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (User::whereNotNull('settings')->get() as $user) {
            $settings = $user->settings;
            $updated = false;

            if (isset($settings['equipment_columns']) && is_array($settings['equipment_columns'])) {
                if (!in_array('division_area', $settings['equipment_columns'])) {
                    $pos = array_search('category', $settings['equipment_columns']);
                    if ($pos !== false) {
                        array_splice($settings['equipment_columns'], $pos + 1, 0, 'division_area');
                    } else {
                        $settings['equipment_columns'][] = 'division_area';
                    }
                    $updated = true;
                }
            }

            if (isset($settings['supplies_columns']) && is_array($settings['supplies_columns'])) {
                if (!in_array('division_area', $settings['supplies_columns'])) {
                    $pos = array_search('category', $settings['supplies_columns']);
                    if ($pos !== false) {
                        array_splice($settings['supplies_columns'], $pos + 1, 0, 'division_area');
                    } else {
                        $settings['supplies_columns'][] = 'division_area';
                    }
                    $updated = true;
                }
            }

            if ($updated) {
                $user->settings = $settings;
                $user->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (User::whereNotNull('settings')->get() as $user) {
            $settings = $user->settings;
            $updated = false;

            if (isset($settings['equipment_columns']) && is_array($settings['equipment_columns'])) {
                $settings['equipment_columns'] = array_values(array_filter($settings['equipment_columns'], fn($col) => $col !== 'division_area'));
                $updated = true;
            }

            if (isset($settings['supplies_columns']) && is_array($settings['supplies_columns'])) {
                $settings['supplies_columns'] = array_values(array_filter($settings['supplies_columns'], fn($col) => $col !== 'division_area'));
                $updated = true;
            }

            if ($updated) {
                $user->settings = $settings;
                $user->save();
            }
        }
    }
};
