<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'impersonating' => $request->session()->has('impersonator_id'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'import' => fn () => $request->session()->get('import'),
            ],
            // Public navbar "Collection" dropdown — categories → their collections.
            'publicNav' => fn () => \App\Models\Category::with(['collections' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($cat) => [
                    'title' => $cat->name_en,
                    'links' => $cat->collections
                        ->map(fn ($c) => ['name' => $c->name_th ?: $c->name_en, 'href' => route('collection.show', $c->id)])
                        ->all(),
                ])->all(),
        ]);
    }
}
