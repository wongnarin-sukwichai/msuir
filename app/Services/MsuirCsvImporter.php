<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Dept;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Parses / validates / commits the "Element ข้อมูล MSUIR" CSV export.
 *
 * Shared by `php artisan msuir:import` (CLI, bulk `--fresh`) and the admin web
 * import wizard (`RepositoryImportController`): upload → validate() dry-run →
 * commit(). The row-parsing rules are the single source of truth for both.
 *
 * CSV shape — single header row, first cell `collection_id`. `contributor` and
 * `subject` headers repeat verbatim and are mapped positionally
 * (`/^contributor/`, `/^subject/`). Author names and subject headings are stored
 * verbatim on the child tables — there is no people/subjects lookup table.
 */
class MsuirCsvImporter
{
    /** Header cells we know about (besides the repeated contributor / subject columns). */
    private const KNOWN_COLUMNS = [
        'collection_id', 'title', 'alternative1', 'alternative2', 'creator',
        'publicsher', 'date', 'format', 'identifier', 'language', 'right',
        'description', 'degree', 'owner', 'update_at', 'created_at',
    ];

    private const REQUIRED_COLUMNS = ['collection_id', 'title'];

    /** @var list<int> */
    private array $validCollectionIds;

    /** @var \Illuminate\Support\Collection<string,int> deps.name => deps.id */
    private $deptByName;

    public function __construct()
    {
        $this->validCollectionIds = Collection::pluck('id')->all();
        $this->deptByName = Dept::pluck('id', 'name');
    }

    /**
     * Read a CSV file into normalised rows. No DB writes.
     *
     * @return array{
     *   header: list<string>,
     *   missingColumns: list<string>,
     *   unexpectedColumns: list<string>,
     *   rows: list<array<string,mixed>>
     * }
     */
    public function parse(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open CSV: {$path}");
        }

        try {
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
                throw new \RuntimeException('No "collection_id" header row found in the CSV.');
            }

            // Column map.
            $col = [];          // single-value header name => index
            $contribIdx = [];   // contributor column indexes, in order
            $subjectIdx = [];   // subject column indexes, in order
            $normalisedHeader = [];
            foreach ($header as $i => $name) {
                $name = strtolower(trim((string) $name));
                $normalisedHeader[] = $name;
                if (Str::startsWith($name, 'contributor')) {
                    $contribIdx[] = $i;
                } elseif (Str::startsWith($name, 'subject')) {
                    $subjectIdx[] = $i;
                } elseif ($name !== '') {
                    $col[$name] = $i;
                }
            }

            $missing = array_values(array_diff(self::REQUIRED_COLUMNS, array_keys($col)));
            $unexpected = array_values(array_filter(
                $normalisedHeader,
                fn (string $n) => $n !== ''
                    && ! in_array($n, self::KNOWN_COLUMNS, true)
                    && ! Str::startsWith($n, 'contributor')
                    && ! Str::startsWith($n, 'subject')
            ));

            $val = static fn (array $row, string $name): string => isset($col[$name])
                ? trim((string) ($row[$col[$name]] ?? ''))
                : '';

            $rows = [];
            $line = 1; // header consumed
            while (($row = fgetcsv($handle)) !== false) {
                $line++;

                // Blank line?
                if ($row === [null] || (count($row) === 1 && trim((string) $row[0]) === '')) {
                    continue;
                }

                $publisher = $val($row, 'publicsher');

                // creator + contributor columns, deduped by (name, role) within the row.
                $seenPeople = [];
                $creator = null;
                $contributors = [];
                $pushPerson = function (string $name, string $role) use (&$seenPeople, &$creator, &$contributors): void {
                    $name = trim($name);
                    if ($name === '') {
                        return;
                    }
                    $key = mb_strtolower($name).'|'.$role;
                    if (isset($seenPeople[$key])) {
                        return;
                    }
                    $seenPeople[$key] = true;
                    if ($role === 'creator') {
                        $creator ??= $name;
                    } else {
                        $contributors[] = $name;
                    }
                };
                $pushPerson($val($row, 'creator'), 'creator');
                foreach ($contribIdx as $i) {
                    $pushPerson((string) ($row[$i] ?? ''), 'contributor');
                }

                // subject columns, deduped by value within the row.
                $seenSubjects = [];
                $subjects = [];
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
                    $subjects[] = $value;
                }

                $altTitles = [];
                foreach (['alternative1', 'alternative2'] as $key) {
                    $alt = $val($row, $key);
                    if ($alt !== '') {
                        $altTitles[] = $alt;
                    }
                }

                // Year: keep the source value as-is (พ.ศ./ค.ศ. mixed); digits only.
                $year = preg_replace('/\D+/', '', $val($row, 'date'));
                $year = $year !== '' ? (int) $year : null;

                $departmentId = $this->resolveDept($publisher);

                $rows[] = [
                    'line' => $line,
                    'collection_id' => (int) $val($row, 'collection_id'),
                    'title' => $val($row, 'title'),
                    'alt_titles' => $altTitles,
                    'creator' => $creator,
                    'contributors' => $contributors,
                    'subjects' => $subjects,
                    'publisher' => $publisher,
                    'department_id' => $departmentId,
                    'department_name' => $departmentId ? $this->deptByName->flip()->get($departmentId) : null,
                    'language' => strtolower($val($row, 'language')) === 'eng' ? 'eng' : 'tha',
                    'year' => $year,
                    'description' => $val($row, 'description') ?: null,
                    'rights' => $val($row, 'right') ?: null,
                    'format' => $val($row, 'format') ?: 'pdf',
                    'fulltext_url' => $val($row, 'identifier') ?: null,
                ];
            }

            return [
                'header' => $normalisedHeader,
                'missingColumns' => $missing,
                'unexpectedColumns' => $unexpected,
                'rows' => $rows,
            ];
        } finally {
            fclose($handle);
        }
    }

    /**
     * Cross-check parsed rows against the DB. Append semantics: a row is a
     * "duplicate" when an item with the same collection_id + title already
     * exists. Adds `status` ('ready'|'duplicate'|'error') and `issues[]` to
     * each row; duplicate + error rows are skipped by commit().
     *
     * @param  list<array<string,mixed>>  $rows  from parse()['rows']
     * @return array{summary: array{total:int,ready:int,duplicate:int,error:int}, rows: list<array<string,mixed>>}
     */
    public function validate(array $rows): array
    {
        // Existing (collection_id, title) pairs, lower-cased for the comparison.
        $existing = Item::query()
            ->select('collection_id', 'title')
            ->get()
            ->map(fn ($i) => $i->collection_id.'|'.mb_strtolower(trim((string) $i->title)))
            ->flip();

        $seenInFile = [];
        $summary = ['total' => 0, 'ready' => 0, 'duplicate' => 0, 'error' => 0];

        foreach ($rows as &$row) {
            $summary['total']++;
            $issues = [];

            if ($row['title'] === '') {
                $issues[] = ['level' => 'error', 'message' => 'ไม่มีชื่อเรื่อง (title)'];
            }
            if (! in_array($row['collection_id'], $this->validCollectionIds, true)) {
                $issues[] = ['level' => 'error', 'message' => "collection_id={$row['collection_id']} ไม่มีในระบบ"];
            }
            if ($row['year'] === null) {
                $issues[] = ['level' => 'warning', 'message' => 'date ไม่มีตัวเลขปี — ปีจะเป็นค่าว่าง'];
            }
            if ($row['creator'] === null && $row['contributors'] === []) {
                $issues[] = ['level' => 'warning', 'message' => 'ไม่มีผู้แต่ง'];
            }
            if ($row['publisher'] !== '' && $row['department_id'] === null
                && ! Str::contains($row['publisher'], 'มหาวิทยาลัยมหาสารคาม')) {
                $issues[] = ['level' => 'warning', 'message' => "จับคู่หน่วยงานไม่ได้: \"{$row['publisher']}\""];
            }

            $hasError = collect($issues)->contains(fn ($x) => $x['level'] === 'error');

            $key = $row['collection_id'].'|'.mb_strtolower(trim($row['title']));
            $isDuplicate = ! $hasError && ($existing->has($key) || isset($seenInFile[$key]));
            if ($isDuplicate) {
                $issues[] = ['level' => 'warning', 'message' => 'พบข้อมูลซ้ำ — จะข้ามแถวนี้ตอนนำเข้า'];
            }
            if (! $hasError && $row['title'] !== '') {
                $seenInFile[$key] = true;
            }

            $row['status'] = $hasError ? 'error' : ($isDuplicate ? 'duplicate' : 'ready');
            $row['issues'] = $issues;
            $summary[$row['status']]++;
        }
        unset($row);

        return ['summary' => $summary, 'rows' => $rows];
    }

    /**
     * Insert every row whose status === 'ready' in one transaction. Error and
     * duplicate rows are skipped.
     *
     * @param  list<array<string,mixed>>  $rows  from validate()['rows']
     * @return array{created:int, skippedError:int, skippedDuplicate:int}
     */
    public function commit(array $rows, string $status = 'approved', ?int $ownerId = null): array
    {
        $created = 0;
        $skippedError = 0;
        $skippedDuplicate = 0;

        DB::transaction(function () use ($rows, $status, $ownerId, &$created, &$skippedError, &$skippedDuplicate) {
            foreach ($rows as $row) {
                if (($row['status'] ?? null) === 'error') {
                    $skippedError++;

                    continue;
                }
                if (($row['status'] ?? null) === 'duplicate') {
                    $skippedDuplicate++;

                    continue;
                }

                $item = Item::create([
                    'collection_id' => $row['collection_id'],
                    'department_id' => $row['department_id'],
                    'owner_id' => $ownerId,
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'year_issued' => $row['year'],
                    'language' => $row['language'],
                    'rights' => $row['rights'],
                    'format' => $row['format'],
                    'degree' => null,
                    'fulltext_url' => $row['fulltext_url'],
                    'status' => $status,
                ]);

                foreach ($row['alt_titles'] as $sort => $alt) {
                    $item->titles()->create(['title' => $alt, 'sort_order' => $sort]);
                }

                $pSort = 0;
                if ($row['creator'] !== null) {
                    $item->people()->create(['name' => $row['creator'], 'role' => 'creator', 'sort_order' => $pSort++]);
                }
                foreach ($row['contributors'] as $name) {
                    $item->people()->create(['name' => $name, 'role' => 'contributor', 'sort_order' => $pSort++]);
                }

                foreach ($row['subjects'] as $sort => $value) {
                    $item->subjects()->create(['value' => $value, 'sort_order' => $sort]);
                }

                $created++;
            }
        });

        return [
            'created' => $created,
            'skippedError' => $skippedError,
            'skippedDuplicate' => $skippedDuplicate,
        ];
    }

    /** Truncate items + child tables (used by `msuir:import --fresh` only). */
    public function truncateAll(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['item_subject', 'item_person', 'item_titles', 'items'] as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();
    }

    /**
     * publicsher → department_id.
     *   1. exact `deps.name` match wins
     *   2. else strip the trailing "… (สำนักงานอธิการบดี) มหาวิทยาลัยมหาสารคาม[,]" suffix
     *      and take the longest `deps.name` that appears as a substring
     *   3. else null (the bare "มหาวิทยาลัยมหาสารคาม" rows land here)
     */
    private function resolveDept(string $publisher): ?int
    {
        $publisher = trim($publisher);
        if ($publisher === '') {
            return null;
        }
        if ($this->deptByName->has($publisher)) {
            return $this->deptByName->get($publisher);
        }

        $clean = preg_replace(
            '/\s*(สำนักงานอธิการบดี\s*)?มหาวิทยาลัยมหาสารคาม\s*,?\s*$/u',
            '',
            $publisher
        );
        $clean = trim(rtrim((string) $clean, " ,\t"));
        if ($clean === '') {
            return null;
        }
        if ($this->deptByName->has($clean)) {
            return $this->deptByName->get($clean);
        }

        $best = null;
        $bestLen = 0;
        foreach ($this->deptByName as $name => $id) {
            if (mb_strpos($clean, $name) !== false && mb_strlen($name) > $bestLen) {
                $best = $id;
                $bestLen = mb_strlen($name);
            }
        }

        return $best;
    }
}
