<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    /** Public single-item page. Only approved items are visible. */
    public function show(int $id): Response
    {
        $item = Item::query()
            ->with(['collection', 'department', 'titles', 'people', 'subjects'])
            ->where('status', 'approved')
            ->findOrFail($id);

        $related = Item::query()
            ->where('collection_id', $item->collection_id)
            ->where('status', 'approved')
            ->whereKeyNot($item->id)
            ->with(['collection', 'creators'])
            ->orderByDesc('id')
            ->limit(4)
            ->get()
            ->map(fn (Item $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'author' => $r->creators->pluck('name')->implode(', ') ?: null,
                'year' => $r->year_issued,
                'type' => $r->collection?->name_th ?: $r->collection?->name_en,
            ]);

        $collectionName = $item->collection?->name_th ?: $item->collection?->name_en;

        return Inertia::render('Item', [
            'item' => [
                'id' => $item->id,
                'title' => $item->title,
                'title_en' => $item->titles->first()?->title,
                'authors' => $item->creators->pluck('name')->values()->all(),
                'contributors' => $item->contributors->pluck('name')->values()->all(),
                'faculty' => $item->department?->name,
                'year' => $item->year_issued,
                'type' => $collectionName,
                'language' => $item->language === 'eng' ? 'English' : 'ภาษาไทย',
                'abstract' => $item->description,
                'keywords' => $item->subjects->pluck('value')->values()->all(),
                'rights' => $item->rights,
                'fulltext_url' => $item->fulltext,
                'collection' => ['id' => $item->collection_id, 'name' => $collectionName],
            ],
            'relatedItems' => $related,
        ]);
    }
}
