<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\Dept;
use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportMsuir extends Command
{
    protected $signature = 'msuir:import
        {path : Path to the CSV export (UTF-8)}
        {--fresh : Wipe items and their child rows before importing}';

    protected $description = 'Import research items from the "Element ข้อมูล MSUIR" CSV into items / item_person / item_subject.';

    /**
     * CSV columns (single header row):
     *   collection_id, title, alternative1, alternative2, creator,
     *   contributor ×8, subject ×5, publicsher (sic), date, format, identifier,
     *   language, right, description, degree, owner, update_at, created_at
     *
     * `contributor` / `subject` headers repeat, so we map columns positionally
     * (a name matching /^contributor/ or /^subject/ is collected as a list).
     * Author names and subject headings are stored verbatim on the child rows —
     * there is no `people` / `subjects` lookup table.
     */
    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $this->error("Cannot open: {$path}");

            return self::FAILURE;
        }

        // Skip a UTF-8 BOM if present.
        if (fread($handle, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Find the header row (first cell === "collection_id").
        $header = null;
        while (($row = fgetcsv($handle)) !== false) {
            if (isset($row[0]) && strtolower(trim((string) $row[0])) === 'collection_id') {
                $header = $row;
                break;
            }
        }

        if ($header === null) {
            $this->error('No "collection_id" header row found in the CSV.');
            fclose($handle);

            return self::FAILURE;
        }

        // Column map.
        $col = [];          // single-value header name => index
        $contribIdx = [];   // contributor column indexes, in order
        $subjectIdx = [];   // subject column indexes, in order
        foreach ($header as $i => $name) {
            $name = strtolower(trim((string) $name));
            if (Str::startsWith($name, 'contributor')) {
                $contribIdx[] = $i;
            } elseif (Str::startsWith($name, 'subject')) {
                $subjectIdx[] = $i;
            } elseif ($name !== '') {
                $col[$name] = $i;
            }
        }

        $val = static fn (array $row, string $name): string => isset($col[$name])
            ? trim((string) ($row[$col[$name]] ?? ''))
            : '';

        if ($this->option('fresh')) {
            $this->warn('Wiping items / item_titles / item_person / item_subject …');
            Schema::disableForeignKeyConstraints();
            foreach (['item_subject', 'item_person', 'item_titles', 'items'] as $table) {
                DB::table($table)->truncate();
            }
            Schema::enableForeignKeyConstraints();
        }

        $validCollectionIds = Collection::pluck('id')->all();
        $deptByName = Dept::pluck('id', 'name');   // exact faculty/office name => id

        $created = 0;
        $skipped = 0;
        $line = 1; // header consumed

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;

                // Blank line?
                if ($row === [null] || (count($row) === 1 && trim((string) $row[0]) === '')) {
                    continue;
                }

                $collectionId = (int) $val($row, 'collection_id');
                $title = $val($row, 'title');

                if ($title === '') {
                    $this->line("  line {$line}: empty title — skipped");
                    $skipped++;

                    continue;
                }
                if (! in_array($collectionId, $validCollectionIds, true)) {
                    $this->line("  line {$line}: unknown collection_id={$collectionId} — skipped");
                    $skipped++;

                    continue;
                }

                // publicsher → department_id (only on an exact match to a seeded dep name).
                $publisher = $val($row, 'publicsher');
                $departmentId = ($publisher !== '' && $deptByName->has($publisher))
                    ? $deptByName->get($publisher)
                    : null;

                $language = strtolower($val($row, 'language')) === 'eng' ? 'eng' : 'tha';

                // Keep the source year value as-is (พ.ศ. / ค.ศ. mixed); strip non-digits.
                $year = preg_replace('/\D+/', '', $val($row, 'date'));
                $year = $year !== '' ? (int) $year : null;

                $item = Item::create([
                    'collection_id' => $collectionId,
                    'department_id' => $departmentId,
                    'owner_id' => null,
                    'title' => $title,
                    'description' => $val($row, 'description') ?: null,
                    'year_issued' => $year,
                    'language' => $language,
                    'rights' => $val($row, 'right') ?: null,
                    'format' => $val($row, 'format') ?: 'pdf',
                    'degree' => $val($row, 'degree') ?: null,
                    'fulltext_url' => $val($row, 'identifier') ?: null,
                    'status' => 'approved',
                ]);

                // Alternative / parallel titles.
                $sort = 0;
                foreach (['alternative1', 'alternative2'] as $key) {
                    $alt = $val($row, $key);
                    if ($alt !== '') {
                        $item->titles()->create(['title' => $alt, 'sort_order' => $sort++]);
                    }
                }

                // People: creator first, then contributor columns. Dedup by (name, role) within the row.
                $seenPeople = [];
                $pSort = 0;

                $addPerson = function (string $name, string $role) use (&$seenPeople, &$pSort, $item): void {
                    $name = trim($name);
                    if ($name === '') {
                        return;
                    }
                    $key = mb_strtolower($name).'|'.$role;
                    if (isset($seenPeople[$key])) {
                        return;
                    }
                    $seenPeople[$key] = true;
                    $item->people()->create([
                        'name' => $name,
                        'role' => $role,
                        'sort_order' => $pSort++,
                    ]);
                };

                $addPerson($val($row, 'creator'), 'creator');
                foreach ($contribIdx as $i) {
                    $addPerson((string) ($row[$i] ?? ''), 'contributor');
                }

                // Subjects: dedup by value within the row, store verbatim.
                $seenSubjects = [];
                $sSort = 0;
                foreach ($subjectIdx as $i) {
                    $value = trim((string) ($row[$i] ?? ''));
                    if ($value === '') {
                        continue;
                    }
                    $key = mb_strtolower($value);
                    if (isset($seenSubjects[$key])) {
                        continue;
                    }
                    $seenSubjects[$key] = true;
                    $item->subjects()->create(['value' => $value, 'sort_order' => $sSort++]);
                }

                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            $this->error("Failed at line {$line}: {$e->getMessage()}");

            return self::FAILURE;
        }

        fclose($handle);

        $this->newLine();
        $this->info("Done. items created: {$created}, rows skipped: {$skipped}");
        $this->line('item_person: '.DB::table('item_person')->count()
            .'   item_subject: '.DB::table('item_subject')->count());

        return self::SUCCESS;
    }
}
