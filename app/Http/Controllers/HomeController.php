<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Item;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $collections = Collection::query()
            ->withCount(['items as items_count' => fn ($q) => $q->where('status', 'approved')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Collection $c) => [
                'id' => $c->id,
                'name' => $c->name_th ?: $c->name_en,
                'name_en' => $c->name_en,
                'count' => (int) $c->items_count,
            ]);

        $latest = Item::query()
            ->where('status', 'approved')
            ->with(['collection', 'creators', 'department'])
            ->orderByDesc('id')
            ->limit(7)
            ->get()
            ->map(fn (Item $it) => [
                'id' => $it->id,
                'title' => $it->title,
                'author' => $it->creators->pluck('name')->implode(', ') ?: null,
                'faculty' => $it->department?->name,
                'collection' => $it->collection?->name_th ?: $it->collection?->name_en,
                'year' => $it->year_issued,
            ]);

        $byCategory = Item::query()
            ->where('items.status', 'approved')
            ->join('collections', 'items.collection_id', '=', 'collections.id')
            ->join('categories', 'collections.category_id', '=', 'categories.id')
            ->selectRaw('categories.name_en as name, count(*) as c')
            ->groupBy('categories.id', 'categories.name_en')
            ->orderByDesc('c')
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->c])
            ->all();

        return Inertia::render('Welcome', [
            'collections' => $collections,
            'recommended' => $latest->take(2)->values()->all(),
            'newReleases' => $latest->slice(2, 5)->values()->all(),
            'stats' => [
                'total' => Item::where('status', 'approved')->count(),
                'byCategory' => $byCategory,
            ],
        ]);
    }
}
