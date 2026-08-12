<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;
use App\Models\Area;
use App\Models\Division;

class BackfillActivityLogNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-activity-log-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfills area_name and div_name in existing activity log properties for equipment and supplies.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill for activity log properties...');

        $areas = Area::withTrashed()->get()->keyBy('id');
        $divisions = Division::withTrashed()->get()->keyBy('id');

        $activities = Activity::whereIn('subject_type', [
            \App\Domain\Equipment\Models\Equipment::class,
            \App\Domain\Supplies\Models\Supply::class
        ])->get();

        $count = 0;

        foreach ($activities as $activity) {
            $properties = $activity->properties;
            $updated = false;

            if ($properties !== null) {
                if ($properties instanceof \Illuminate\Support\Collection) {
                    if (!$properties->has('area_name') && $properties->has('area_id') && $properties->get('area_id')) {
                        $area = $areas->get($properties->get('area_id'));
                        $properties->put('area_name', $area ? $area->area_name : null);
                        $updated = true;
                    }
                    if (!$properties->has('div_name') && $properties->has('division_id') && $properties->get('division_id')) {
                        $div = $divisions->get($properties->get('division_id'));
                        $properties->put('div_name', $div ? $div->div_name : null);
                        $updated = true;
                    }
                } elseif (is_array($properties)) {
                    if (!isset($properties['area_name']) && isset($properties['area_id']) && $properties['area_id']) {
                        $area = $areas->get($properties['area_id']);
                        $properties['area_name'] = $area ? $area->area_name : null;
                        $updated = true;
                    }
                    if (!isset($properties['div_name']) && isset($properties['division_id']) && $properties['division_id']) {
                        $div = $divisions->get($properties['division_id']);
                        $properties['div_name'] = $div ? $div->div_name : null;
                        $updated = true;
                    }
                }
            }

            if ($updated) {
                $activity->properties = $properties;
                $activity->save();
                $count++;
            }
        }

        $this->info("Backfill complete. Updated {$count} activity records.");
    }
}
