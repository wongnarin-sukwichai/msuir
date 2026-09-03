<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                // The URL itself is never sent to the browser — download goes through the auth-gated proxy.
                'has_fulltext' => $item->fulltext_url !== null || $item->fulltext_path !== null,
                'collection' => ['id' => $item->collection_id, 'name' => $collectionName],
            ],
            'relatedItems' => $related,
        ]);
    }

    /**
     * Auth-gated full-text access. Guests hit the `auth` middleware first and are
     * redirected to /login; logged-in users get the file (or a redirect to the
     * external URL).
     */
    public function download(int $id): RedirectResponse|StreamedResponse
    {
        $item = Item::where('status', 'approved')->findOrFail($id);

        // TODO(stats): record a 'download' event here once item_events exists.

        if ($item->fulltext_path && Storage::disk('public')->exists($item->fulltext_path)) {
            return Storage::disk('public')->download($item->fulltext_path);
        }
        if ($item->fulltext_url) {
            return redirect()->away($item->fulltext_url);
        }

        abort(404, 'ไม่มีไฟล์ฉบับเต็มสำหรับรายการนี้');
    }
}
