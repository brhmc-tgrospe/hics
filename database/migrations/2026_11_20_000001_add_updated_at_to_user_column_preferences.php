<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

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
                if (!in_array('updated_at', $settings['equipment_columns'], true)) {
                    $settings['equipment_columns'][] = 'updated_at';
                    $updated = true;
                }
            }

            if (isset($settings['supplies_columns']) && is_array($settings['supplies_columns'])) {
                if (!in_array('updated_at', $settings['supplies_columns'], true)) {
                    $settings['supplies_columns'][] = 'updated_at';
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
                $settings['equipment_columns'] = array_values(array_filter(
                    $settings['equipment_columns'],
                    fn ($col) => $col !== 'updated_at'
                ));
                $updated = true;
            }

            if (isset($settings['supplies_columns']) && is_array($settings['supplies_columns'])) {
                $settings['supplies_columns'] = array_values(array_filter(
                    $settings['supplies_columns'],
                    fn ($col) => $col !== 'updated_at'
                ));
                $updated = true;
            }

            if ($updated) {
                $user->settings = $settings;
                $user->save();
            }
        }
    }
};
