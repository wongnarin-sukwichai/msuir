<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Collection;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    /**
     * Fixed taxonomy — 3 categories (nav-dropdown grouping only) + 13 collections.
     * IDs are pinned: the CSV's `collection_id` column points straight at collections.id.
     * Re-run safely with:  php artisan db:seed --class=CollectionSeeder
     */
    public function run(): void
    {
        $categories = [
            1 => 'INSTITUTIONAL REPOSITORY (MSU-IR)',
            2 => 'ARCHIVE AND RARE BOOKS',
            3 => 'MULTIMEDIA & E-LEARNING',
        ];

        foreach ($categories as $id => $nameEn) {
            Category::updateOrCreate(['id' => $id], [
                'name_en'    => $nameEn,
                'slug'       => \Illuminate\Support\Str::slug($nameEn),
                'sort_order' => $id,
            ]);
        }

        // [id, name_en, name_th (old book_type label, null when none), category_id]
        $collections = [
            [1,  'MSU e-Theses',             'วิทยานิพนธ์',           1],
            [2,  'MSU e-Researches',          'งานวิจัย',              1],
            [3,  'MSU e-Articles',            'บทความ',               1],
            [4,  'MSU e-Books',               'หนังสืออิเล็กทรอนิกส์', 1],
            [5,  'MSU e-Manual',              'คู่มือ',                1],
            [6,  'Local Wisdom Collection',   null,                   2],
            [7,  'Rare Books',                null,                   2],
            [8,  'Manuscripts',               null,                   2],
            [9,  'Historical Photographs',    null,                   2],
            [10, 'MSU Multimedia Archives',   null,                   3],
            [11, 'E-Lecture Series',          null,                   3],
            [12, 'Research Reports',          null,                   3],
            [13, 'Free e-Books',              null,                   3],
        ];

        $orderInCategory = [];
        foreach ($collections as [$id, $nameEn, $nameTh, $categoryId]) {
            $orderInCategory[$categoryId] = ($orderInCategory[$categoryId] ?? 0) + 1;

            Collection::updateOrCreate(['id' => $id], [
                'category_id' => $categoryId,
                'name_en'     => $nameEn,
                'name_th'     => $nameTh,
                'slug'        => \Illuminate\Support\Str::slug($nameEn),
                'sort_order'  => $orderInCategory[$categoryId],
            ]);
        }
    }
}
