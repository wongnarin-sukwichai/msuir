<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Dept;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RepositoryController extends Controller
{
    /**
     * Flow B — create one item from the "เพิ่มรายการ" wizard.
     * staff (role 2) → status 'pending'; admin (role 3) → 'approved'. owner_id = the submitter.
     * Route middleware is role:2, so both staff and admin reach this.
     */
    public function store(Request $request): RedirectResponse
    {
        $isAdmin = (int) $request->user()->role_level >= 3;

        $data = $request->validate($this->rules(fulltextRequired: true));

        $path = $request->hasFile('fulltext_file')
            ? $request->file('fulltext_file')->store('fulltext', 'public')
            : null;

        DB::transaction(function () use ($data, $request, $isAdmin, $path) {
            $item = Item::create([
                'collection_id' => $data['collection_id'],
                'department_id' => $data['department_id'] ?? null,
                'owner_id' => $request->user()->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'year_issued' => $data['year_issued'] ?? null,
                'language' => $data['language'],
                'rights' => $data['rights'] ?? null,
                'format' => 'pdf',
                'degree' => $data['degree'] ?? null,
                'fulltext_url' => $data['fulltext_url'] ?? null,
                'fulltext_path' => $path,
                'status' => $isAdmin ? 'approved' : 'pending',
            ]);

            $this->syncChildren($item, $data);
        });

        return back()->with('success', $isAdmin
            ? 'เพิ่มรายการเข้าคลังแล้ว (เผยแพร่ทันที)'
            : 'ส่งรายการเข้าคิวตรวจสอบแล้ว รอผู้ดูแลอนุมัติ');
    }

    /**
     * Flow D — single flat edit page. Reachable by admin (any item) or the owner
     * while the item is still pending / action_required.
     */
    public function edit(Request $request, Item $item): Response
    {
        $this->authorizeItemEdit($request, $item);
        $item->load(['titles', 'people', 'subjects']);

        return Inertia::render('Repository/ItemEdit', [
            'item' => [
                'id' => $item->id,
                'collection_id' => $item->collection_id,
                'title' => $item->title,
                'language' => $item->language,
                'year_issued' => $item->year_issued,
                'department_id' => $item->department_id,
                'rights' => $item->rights,
                'degree' => $item->degree,
                'description' => $item->description,
                'fulltext_url' => $item->fulltext_url,
                'fulltext_path' => $item->fulltext_path,
                'status' => $item->status,
                'review_note' => $item->review_note,
                'alt_titles' => $item->titles->pluck('title')->all(),
                'creator' => optional($item->people->firstWhere('role', 'creator'))->name,
                'contributors' => $item->people->where('role', 'contributor')->pluck('name')->values()->all(),
                'subjects' => $item->subjects->pluck('value')->all(),
            ],
            'collections' => Collection::orderBy('sort_order')->get(['id', 'name_en', 'name_th'])
                ->map(fn (Collection $c) => ['id' => $c->id, 'name' => $c->name_th ?: $c->name_en])->all(),
            'departments' => Dept::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $this->authorizeItemEdit($request, $item);

        // On edit the full text may already be stored — only require one when neither is present.
        $data = $request->validate($this->rules(
            fulltextRequired: $item->fulltext_url === null && $item->fulltext_path === null,
        ));

        $path = $item->fulltext_path;
        if ($request->hasFile('fulltext_file')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $request->file('fulltext_file')->store('fulltext', 'public');
        }
        // A pasted URL supersedes any stored file.
        if (filled($data['fulltext_url'] ?? null) && $path) {
            Storage::disk('public')->delete($path);
            $path = null;
        }

        $isAdmin = (int) $request->user()->role_level >= 3;

        DB::transaction(function () use ($item, $data, $path, $isAdmin) {
            $update = [
                'collection_id' => $data['collection_id'],
                'department_id' => $data['department_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'year_issued' => $data['year_issued'] ?? null,
                'language' => $data['language'],
                'rights' => $data['rights'] ?? null,
                'degree' => $data['degree'] ?? null,
                'fulltext_url' => $data['fulltext_url'] ?? null,
                'fulltext_path' => $path,
            ];

            // A non-admin fixing a returned item resubmits it for review.
            if (! $isAdmin && $item->status === 'action_required') {
                $update['status'] = 'pending';
                $update['review_note'] = null;
            }

            $item->update($update);

            $item->titles()->delete();
            $item->people()->delete();
            $item->subjects()->delete();
            $this->syncChildren($item, $data);
        });

        return redirect()->route('dashboard')->with('success', 'บันทึกการแก้ไขรายการแล้ว');
    }

    /** Flow E — approve a queued item (→ approved, clears any review note). Admin only. */
    public function approve(Item $item): RedirectResponse
    {
        $item->update(['status' => 'approved', 'review_note' => null]);

        return back()->with('success', 'อนุมัติเผยแพร่รายการแล้ว');
    }

    /** Flow E — send a queued item back to its owner with a note (→ action_required). Admin only. */
    public function returnForEdit(Request $request, Item $item): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $item->update(['status' => 'action_required', 'review_note' => $data['note']]);

        return back()->with('success', 'ส่งรายการกลับให้เจ้าของแก้ไขแล้ว');
    }

    /** Soft-delete an item from the repository. Admin only (route middleware). */
    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        return back()->with('success', 'ลบรายการออกจากคลังข้อมูลแล้ว');
    }

    // --- helpers -------------------------------------------------------------

    /** @return array<string, mixed> */
    private function rules(bool $fulltextRequired): array
    {
        return [
            'collection_id' => ['required', 'integer', 'exists:collections,id'],
            'title' => ['required', 'string', 'max:2000'],
            'language' => ['required', 'in:tha,eng'],
            'alt_titles' => ['array'],
            'alt_titles.*' => ['nullable', 'string', 'max:2000'],
            'creator' => ['nullable', 'string', 'max:255'],
            'contributors' => ['array'],
            'contributors.*' => ['nullable', 'string', 'max:255'],
            'year_issued' => ['nullable', 'integer', 'min:1', 'max:2600'],
            'department_id' => ['nullable', 'integer', 'exists:deps,id'],
            'rights' => ['nullable', 'string', 'max:255'],
            'degree' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'subjects' => ['array'],
            'subjects.*' => ['nullable', 'string', 'max:255'],
            'fulltext_url' => array_filter(['nullable', 'url', 'max:2000', $fulltextRequired ? 'required_without:fulltext_file' : null]),
            'fulltext_file' => array_filter(['nullable', 'file', 'mimes:pdf', 'max:51200', $fulltextRequired ? 'required_without:fulltext_url' : null]),
        ];
    }

    /** Rebuild the item's title / person / subject child rows from a validated payload. */
    private function syncChildren(Item $item, array $data): void
    {
        $sort = 0;
        foreach (array_filter($data['alt_titles'] ?? [], 'filled') as $t) {
            $item->titles()->create(['title' => $t, 'sort_order' => $sort++]);
        }

        $pSort = 0;
        if (filled($data['creator'] ?? null)) {
            $item->people()->create(['name' => $data['creator'], 'role' => 'creator', 'sort_order' => $pSort++]);
        }
        foreach (array_filter($data['contributors'] ?? [], 'filled') as $name) {
            $item->people()->create(['name' => $name, 'role' => 'contributor', 'sort_order' => $pSort++]);
        }

        $sSort = 0;
        foreach (array_filter($data['subjects'] ?? [], 'filled') as $value) {
            $item->subjects()->create(['value' => $value, 'sort_order' => $sSort++]);
        }
    }

    private function authorizeItemEdit(Request $request, Item $item): void
    {
        $isAdmin = (int) $request->user()->role_level >= 3;

        abort_unless(
            $isAdmin || (
                $item->owner_id === $request->user()->id
                && in_array($item->status, ['pending', 'action_required'], true)
            ),
            403,
            'ไม่มีสิทธิ์แก้ไขรายการนี้',
        );
    }
}
