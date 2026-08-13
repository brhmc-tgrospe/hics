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
            $properties = $activity->properties instanceof \Illuminate\Support\Collection ? $activity->properties : collect($activity->properties ?? []);
            $attributeChanges = $activity->attribute_changes instanceof \Illuminate\Support\Collection ? $activity->attribute_changes : collect($activity->attribute_changes ?? []);
            
            $updated = false;

            $areaId = $properties->get('area_id') ?? $properties->get('attributes')['area_id'] ?? $attributeChanges->get('attributes')['area_id'] ?? null;
            $divId = $properties->get('division_id') ?? $properties->get('attributes')['division_id'] ?? $attributeChanges->get('attributes')['division_id'] ?? null;

            if ($areaId && !$properties->has('area_name')) {
                $area = $areas->get($areaId);
                $properties->put('area_name', $area ? $area->area_name : null);
                $updated = true;
            }

            if ($divId && !$properties->has('div_name')) {
                $div = $divisions->get($divId);
                $properties->put('div_name', $div ? $div->div_name : null);
                $updated = true;
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
