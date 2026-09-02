<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Dept;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = (int) $user->role_level >= 3;

        // Member management is admin-only — staff never receives member PII.
        $members = $isAdmin
            ? User::with('department')->orderByDesc('id')->get()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role_level' => $u->role_level,
                'is_msu_member' => $u->is_msu_member,
                'department_id' => $u->department_id,
                'department_name' => $u->department?->name,
                'status' => $u->status,
            ])
            : [];

        return Inertia::render('Dashboard', [
            'members' => $members,
            // Faculty list is not PII and the "เพิ่มรายการ" wizard needs it, so always send it.
            'departments' => Dept::orderBy('name')->get(['id', 'name']),
            'repository' => $this->repositoryPayload($request, $isAdmin, (int) $user->id),
            'stats' => $this->statsPayload($isAdmin, (int) $user->id),
            'queue' => $this->queuePayload($isAdmin, (int) $user->id),
        ]);
    }

    /**
     * Aggregate figures for the overview + analytics tabs and the sidebar badges.
     * Role-aware: staff see only their own contributions, admin sees the whole repo.
     */
    private function statsPayload(bool $isAdmin, int $userId): array
    {
        $scope = fn () => Item::query()->when(! $isAdmin, fn ($q) => $q->where('owner_id', $userId));

        $byStatus = $scope()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        $byCollection = $scope()
            ->selectRaw('collection_id, count(*) c')->groupBy('collection_id')->pluck('c', 'collection_id');
        $collectionNames = Collection::orderBy('sort_order')->get(['id', 'name_en', 'name_th']);

        $byLanguage = $scope()->selectRaw('language, count(*) c')->groupBy('language')->pluck('c', 'language');

        $byYear = $scope()->whereNotNull('year_issued')
            ->selectRaw('year_issued, count(*) c')->groupBy('year_issued')
            ->orderByDesc('year_issued')->limit(8)->pluck('c', 'year_issued');

        $topFacultyIds = $scope()->whereNotNull('department_id')
            ->selectRaw('department_id, count(*) c')->groupBy('department_id')
            ->orderByDesc('c')->limit(6)->pluck('c', 'department_id');
        $facultyNames = Dept::whereIn('id', $topFacultyIds->keys())->pluck('name', 'id');

        $recent = $scope()->with(['collection', 'creators'])->orderByDesc('id')->limit(6)->get()
            ->map(fn (Item $it) => [
                'id' => $it->id,
                'title' => $it->title,
                'collection' => $it->collection?->name_th ?: $it->collection?->name_en,
                'author' => $it->creators->pluck('name')->implode(', ') ?: null,
                'status' => $it->status,
            ]);

        return [
            'total' => (int) $byStatus->sum(),
            'approved' => (int) ($byStatus['approved'] ?? 0),
            'pending' => (int) ($byStatus['pending'] ?? 0),
            'actionRequired' => (int) ($byStatus['action_required'] ?? 0),
            'byCollection' => $collectionNames
                ->map(fn (Collection $c) => [
                    'name' => $c->name_th ?: $c->name_en,
                    'count' => (int) ($byCollection[$c->id] ?? 0),
                ])
                ->filter(fn ($r) => $r['count'] > 0)
                ->sortByDesc('count')->values()->all(),
            'byLanguage' => [
                'tha' => (int) ($byLanguage['tha'] ?? 0),
                'eng' => (int) ($byLanguage['eng'] ?? 0),
            ],
            'byYear' => $byYear->map(fn ($c, $y) => ['year' => (int) $y, 'count' => (int) $c])->values()->all(),
            'topFaculties' => $topFacultyIds
                ->map(fn ($c, $id) => ['name' => $facultyNames[$id] ?? '—', 'count' => (int) $c])
                ->values()->all(),
            'recent' => $recent->all(),
        ];
    }

    /**
     * Flow E — the "คิวตรวจสอบข้อมูล" tab.
     *   admin → every item still pending / needing changes, with owner name.
     *   staff → their own items (all statuses), read-only, with the admin's note.
     */
    private function queuePayload(bool $isAdmin, int $userId): array
    {
        $items = Item::query()
            ->with(['collection', 'creators', 'owner'])
            ->when(
                $isAdmin,
                fn ($q) => $q->whereIn('status', ['pending', 'action_required']),
                fn ($q) => $q->where('owner_id', $userId),
            )
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return [
            'items' => $items->map(fn (Item $it) => [
                'id' => $it->id,
                'title' => $it->title,
                'collection' => $it->collection?->name_th ?: $it->collection?->name_en,
                'author' => $it->creators->pluck('name')->implode(', ') ?: null,
                'year' => $it->year_issued,
                'status' => $it->status,
                'reviewNote' => $it->review_note,
                'owner' => $isAdmin ? $it->owner?->name : null,
                'submittedAt' => optional($it->created_at)->format('Y-m-d'),
            ])->all(),
        ];
    }

    /**
     * Flow C — the "จัดการคลังข้อมูล" table. Role-aware: staff see only their own
     * items, admin sees everything. Search / collection / status filters + paging
     * round-trip through this same endpoint (`router.get(route('dashboard'), …, { only: ['repository'] })`).
     */
    private function repositoryPayload(Request $request, bool $isAdmin, int $userId): array
    {
        $q = trim((string) $request->query('repo_q', ''));
        $collectionId = (int) $request->query('repo_collection', 0);
        $status = (string) $request->query('repo_status', '');

        $items = Item::query()
            ->with(['collection', 'creators'])
            ->when(! $isAdmin, fn ($query) => $query->where('owner_id', $userId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhereHas('people', fn ($p) => $p->where('name', 'like', "%{$q}%"));
                    if (ctype_digit($q)) {
                        $w->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($collectionId > 0, fn ($query) => $query->where('collection_id', $collectionId))
            ->when(in_array($status, ['pending', 'approved', 'action_required'], true),
                fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return [
            'items' => collect($items->items())->map(fn (Item $it) => [
                'id' => $it->id,
                'collection' => $it->collection?->name_th ?: $it->collection?->name_en,
                'title' => $it->title,
                'author' => $it->creators->pluck('name')->implode(', ') ?: null,
                'year' => $it->year_issued,
                'status' => $it->status,
                'canEdit' => $isAdmin || in_array($it->status, ['pending', 'action_required'], true),
            ])->all(),
            'pagination' => [
                'total' => $items->total(),
                'currentPage' => $items->currentPage(),
                'lastPage' => $items->lastPage(),
                'perPage' => $items->perPage(),
            ],
            'filters' => [
                'q' => $q,
                'collection' => $collectionId > 0 ? $collectionId : null,
                'status' => $status !== '' ? $status : null,
            ],
            'collections' => Collection::orderBy('sort_order')->get(['id', 'name_en', 'name_th'])
                ->map(fn (Collection $c) => ['id' => $c->id, 'name' => $c->name_th ?: $c->name_en])
                ->all(),
        ];
    }
}
