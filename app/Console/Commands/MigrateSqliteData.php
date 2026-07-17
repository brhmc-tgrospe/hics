<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Supplies\Models\Supply;
use PDO;

class MigrateSqliteData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:migrate-sqlite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from legacy SQLite database to MySQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sqlitePath = base_path('inventory.db');
        if (!file_exists($sqlitePath)) {
            $this->error("SQLite database not found at {$sqlitePath}");
            return;
        }

        $this->info("Connecting to SQLite at {$sqlitePath}...");
        try {
            $pdo = new PDO('sqlite:' . $sqlitePath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Migrate Equipment
            $this->info("Migrating Equipment...");
            $stmt = $pdo->query('SELECT * FROM equipment');
            $equipmentData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($equipmentData as $row) {
                // Remove id to allow MySQL auto-increment or keep it to match exactly
                Equipment::create($row);
            }
            $this->info("Migrated " . count($equipmentData) . " equipment records.");

            // Migrate Supplies
            $this->info("Migrating Supplies...");
            $stmt = $pdo->query('SELECT * FROM supplies');
            $suppliesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($suppliesData as $row) {
                Supply::create($row);
            }
            $this->info("Migrated " . count($suppliesData) . " supplies records.");

            $this->info("Data migration complete!");

        } catch (\Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
        }
    }
}
