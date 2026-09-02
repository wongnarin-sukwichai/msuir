<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Dept;
use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CollectionController extends Controller
{
    public function show(Request $request, int $id)
    {
        $collection = Collection::with('category')->findOrFail($id);

        // --- current filter state (round-trips through the URL) ---
        $search = trim((string) $request->query('q', ''));
        $years = array_values(array_filter(array_map('intval', (array) $request->query('years', []))));
        $faculties = array_values(array_filter(array_map('strval', (array) $request->query('faculties', []))));
        $sort = in_array($request->query('sort'), ['date', 'title'], true) ? $request->query('sort') : 'date';

        // --- items query (public: approved only) ---
        $query = $collection->items()
            ->where('status', 'approved')
            ->with([
                'titles' => fn ($q) => $q->limit(1),
                'creators',
                'department',
            ]);

        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhereHas('people', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('titles', fn ($t) => $t->where('title', 'like', "%{$search}%"));
            });
        }
        if ($years !== []) {
            $query->whereIn('year_issued', $years);
        }
        if ($faculties !== []) {
            $query->whereHas('department', fn ($d) => $d->whereIn('name', $faculties));
        }

        $sort === 'title'
            ? $query->orderBy('title')
            : $query->orderByDesc('year_issued')->orderByDesc('id');

        $items = $query->paginate(10)->withQueryString();

        // --- facet options: from the whole (approved) collection, not the filtered set ---
        $availableYears = $collection->items()
            ->where('status', 'approved')
            ->whereNotNull('year_issued')
            ->distinct()
            ->orderByDesc('year_issued')
            ->pluck('year_issued');

        $facultyIds = $collection->items()
            ->where('status', 'approved')
            ->whereNotNull('department_id')
            ->distinct()
            ->pluck('department_id');
        $availableFaculties = Dept::whereIn('id', $facultyIds)->orderBy('name')->pluck('name');

        return Inertia::render('Collection', [
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name_th ?: $collection->name_en,
                'name_en' => $collection->name_en,
                'category' => $collection->category?->name_en,
                'description' => null, // no per-collection description in the DB yet
                'icon' => 'fa-book',
            ],
            'items' => collect($items->items())->map(fn (Item $it) => [
                'id' => $it->id,
                'title' => $it->title,
                'title_en' => $it->titles->first()?->title,
                'author' => $it->creators->pluck('name')->implode(', ') ?: null,
                'faculty' => $it->department?->name,
                'year' => $it->year_issued,
                'language' => $it->language,
                'abstract' => $it->description,
            ])->all(),
            'pagination' => [
                'total' => $items->total(),
                'currentPage' => $items->currentPage(),
                'lastPage' => $items->lastPage(),
                'perPage' => $items->perPage(),
            ],
            'filters' => [
                'q' => $search,
                'years' => $years,
                'faculties' => $faculties,
                'sort' => $sort,
            ],
            'availableYears' => $availableYears,
            'availableFaculties' => $availableFaculties,
        ]);
    }
}
