<?php

namespace App\Console\Commands;

use App\Services\MsuirCsvImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMsuir extends Command
{
    protected $signature = 'msuir:import
        {path : Path to the CSV export (UTF-8)}
        {--fresh : Wipe items and their child rows before importing}';

    protected $description = 'Import research items from the "Element ข้อมูล MSUIR" CSV into items / item_person / item_subject.';

    public function handle(MsuirCsvImporter $importer): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        try {
            $parsed = $importer->parse($path);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($parsed['missingColumns'] !== []) {
            $this->error('Missing required column(s): '.implode(', ', $parsed['missingColumns']));

            return self::FAILURE;
        }
        if ($parsed['unexpectedColumns'] !== []) {
            $this->warn('Unrecognised column(s) ignored: '.implode(', ', $parsed['unexpectedColumns']));
        }

        if ($this->option('fresh')) {
            $this->warn('Wiping items / item_titles / item_person / item_subject …');
            $importer->truncateAll();
        }

        $validated = $importer->validate($parsed['rows']);
        foreach ($validated['rows'] as $row) {
            foreach ($row['issues'] as $issue) {
                if ($issue['level'] === 'error') {
                    $this->line("  line {$row['line']}: {$issue['message']} — skipped");
                }
            }
        }

        try {
            $result = $importer->commit($validated['rows'], status: 'approved');
        } catch (\Throwable $e) {
            $this->error("Import failed (rolled back): {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Done. items created: {$result['created']}, "
            ."skipped: {$result['skippedError']} error / {$result['skippedDuplicate']} duplicate");
        $this->line('item_person: '.DB::table('item_person')->count()
            .'   item_subject: '.DB::table('item_subject')->count());

        return self::SUCCESS;
    }
}
