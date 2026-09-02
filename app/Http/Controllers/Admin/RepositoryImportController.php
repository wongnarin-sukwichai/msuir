<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MsuirCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Flow A — admin-only CSV bulk import (3-step wizard in the dashboard "นำเข้าข้อมูล .csv" tab).
 *   1. GET  template          → download the column template
 *   2. POST import/validate   → store upload at storage/app/imports/{uuid}.csv, dry-run, flash a report
 *   3. POST import/commit     → re-parse the stored file, insert (status=approved), delete the file
 *
 * The wizard reads the report/result off the `import` flash key (see HandleInertiaRequests).
 */
class RepositoryImportController extends Controller
{
    public function template(): BinaryFileResponse
    {
        return response()->download(
            storage_path('app/templates/elementmsuir-template.csv'),
            'elementmsuir-template.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function validateUpload(Request $request, MsuirCsvImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120'], // 5 MB
        ]);

        if (strtolower($request->file('file')->getClientOriginalExtension()) !== 'csv') {
            throw ValidationException::withMessages(['file' => 'รองรับเฉพาะไฟล์ .csv เท่านั้น']);
        }

        $uuid = (string) Str::uuid();
        $stored = $request->file('file')->storeAs('imports', $uuid.'.csv');

        try {
            $parsed = $importer->parse(Storage::path($stored));
        } catch (\RuntimeException $e) {
            Storage::delete($stored);

            return back()->with('import', ['ok' => false, 'error' => $e->getMessage()]);
        }

        $validated = $importer->validate($parsed['rows']);

        return back()->with('import', [
            'ok' => true,
            'uuid' => $uuid,
            'filename' => $request->file('file')->getClientOriginalName(),
            'missingColumns' => $parsed['missingColumns'],
            'unexpectedColumns' => $parsed['unexpectedColumns'],
            'summary' => $validated['summary'],
            'preview' => collect($validated['rows'])->take(30)->map(fn (array $r) => [
                'line' => $r['line'],
                'title' => $r['title'],
                'collection_id' => $r['collection_id'],
                'creator' => $r['creator'],
                'department_name' => $r['department_name'],
                'year' => $r['year'],
                'status' => $r['status'],
                'issues' => $r['issues'],
            ])->all(),
        ]);
    }

    public function commit(Request $request, MsuirCsvImporter $importer): RedirectResponse
    {
        $data = $request->validate(['uuid' => ['required', 'uuid']]);
        $stored = 'imports/'.$data['uuid'].'.csv';

        if (! Storage::exists($stored)) {
            return back()->with('import', ['ok' => false, 'error' => 'ไฟล์หมดอายุแล้ว กรุณาอัปโหลดใหม่']);
        }

        try {
            $parsed = $importer->parse(Storage::path($stored));
            $validated = $importer->validate($parsed['rows']);
            $result = $importer->commit($validated['rows'], status: 'approved');
        } catch (\Throwable $e) {
            return back()->with('import', ['ok' => false, 'error' => 'นำเข้าไม่สำเร็จ: '.$e->getMessage()]);
        } finally {
            Storage::delete($stored);
        }

        return back()
            ->with('import', ['ok' => true, 'done' => true, 'result' => $result])
            ->with('success', "นำเข้าสำเร็จ {$result['created']} รายการ");
    }
}
