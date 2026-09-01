# MSU IR — Institutional Repository (Mahasarakham University)

Laravel 12 + Inertia.js v2 + Vue 3 (TypeScript) + Tailwind CSS. XAMPP/MySQL locally, DB name `msuir_db`.

## Conventions established so far

- **Admin backend is one file**: `resources/js/pages/Dashboard.vue` — sidebar + all tabs (dashboard/repository/approvals/analytics/members/settings) live in this single SFC via `activeTab` ref, not separate routed pages. No shared `AppSidebarLayout` is used here (that layout exists in `resources/js/layouts/app/` but is currently disconnected/unused).
- **Public pages** use `resources/js/layouts/PublicLayout.vue` (Welcome, Collection, Item, Tutorial) — wraps `PublicNavbar`, `LoginModal`, `CookieBanner`, `ImpersonationBanner`.
- **"Collection" is the canonical name** for the browsable subject grouping (e.g. "MSU e-Theses"). Route `/collection/{id}` → `CollectionController@show` → renders `Collection.vue` with prop `collection`. Do NOT reintroduce the word "category" anywhere (renamed 2026-09-01). `book_type` (material type: งานวิจัย / คู่มือ / e-book) is a **separate independent dimension** from `collection`.
- **Inertia action pattern**: admin mutations (create/update/delete/toggle) call `router.post/put/patch/delete(route('...'), payload, { preserveScroll: true, onSuccess, onError })` and the controller just `return back()->with(...)` — no client-side state mutation, Inertia re-fetches props automatically. Follow this pattern for any new admin CRUD.
- **Confirm-before-destructive-action**: use `Swal.fire({...})` (sweetalert2) for delete/impersonate/logout confirmations, not `confirm()`. See `handleDeleteMember`/`handleImpersonate`/`logout` in Dashboard.vue for the exact options object to reuse.
- **Toast**: `triggerToast(message, 'success'|'warning'|'danger')` — local component pattern, not a global store.
- **Styling language**: `rounded-2xl`/`rounded-[2.5rem]` cards, `bg-[#1e3a8a]` primary / `#facc15` yellow accent, Font Awesome 6 icons (loaded via CDN `<link>` in each page's `<Head>`), Sarabun font. Modals: gradient header (`from-[#1e3a8a] via-[#1e40af] to-blue-700`) + white form body, `modal-fade` transition.
- **Custom dropdowns**: native `<select>` open-list can't be restyled cross-browser. Use `resources/js/components/ModernSelect.vue` (self-built, `@vueuse/core` `onClickOutside`) instead of `<select>` anywhere inside this admin UI's modals.
- **Modal card gotcha**: the outer modal card must NOT have `overflow-hidden` on the whole card if it contains a `ModernSelect` (the dropdown list gets clipped). Put `overflow-hidden rounded-t-[2.5rem]` on the gradient header div only (to clip its decorative blur circle), keep the outer card overflow visible.

## Data model built so far

**`users` table** (extended from the starter kit's default):
- `role_level` (tinyint, pre-existing): 1 = สมาชิกทั่วไป, 3 = ผู้ดูแลระบบ (level 2/staff exists in DB but UI only exposes 1 and 3)
- `status`: `'active' | 'suspended'`, toggled by clicking the status badge in the members table (not a separate icon anymore)
- `is_msu_member` (boolean): true = มมส. (@msu.ac.th, logs in via Google OAuth, no password), false = บุคคลภายนอก (full email + password)
- `department_id` (nullable FK → `deps.id`, `nullOnDelete`)
- Soft deletes enabled (`deleted_at`)

**`deps` table**: `id, name` — 49 rows seeded via `database/seeders/DepsSeeder.php` (real MSU faculty/office list, confirmed by user). Reseed with `php artisan db:seed --class=DepsSeeder`.

**Admin member management** (`resources/js/pages/Dashboard.vue`, tab `members`):
- Real DB-backed (no more mock), props `members` + `departments` come from `DashboardController@index`
- Routes (all under `role:3` middleware, alias registered in `bootstrap/app.php`):
  - `POST admin/members` → `MemberController@store`
  - `PUT admin/members/{member}` → `MemberController@update`
  - `PATCH admin/members/{member}/status` → `toggleStatus`
  - `DELETE admin/members/{member}` → `destroy` (soft delete)
  - `POST admin/members/{member}/impersonate` → `impersonate` (real session-swap, blocked for self/other-admins/suspended accounts)
  - `POST impersonate/leave` (auth-only, not role:3) → `ImpersonateController@leave`
- Impersonation state shared via `auth.impersonating` (Inertia shared prop, `HandleInertiaRequests`), banner in `ImpersonationBanner.vue`
- "เพิ่มสมาชิก"/"แก้ไขข้อมูลสมาชิก" modals: MSU-member toggle up top decides email shape (`local@msu.ac.th` compound input, no password field) vs external (full email + password required on create, optional-to-change on edit)

## In progress / next up — Items & Categories schema (research content import)

**Not yet implemented** — migrations not written yet. Design is LOCKED as of 2026-09-01 (round 6). The real CSV has been re-exported with the final `collection_id` column.

**Real data** (`Element ข้อมูล MSUIR.csv`, 279 rows). Flat 1-row-per-item export, much simpler than the ubru.ac.th sample pages (structural reference only, NOT MSU content — confirmed). **Single header row** (data starts row 2), 29 columns:
`collection_id, title, alternative1, alternative2, creator, contributor ×8, subject ×5, publicsher (sic), date, format, identifier, language, right, description, degree, owner, update_at, created_at`
- The `contributor` and `subject` headers **repeat verbatim** (not `contributor1..8`) → `msuir:import` maps them positionally (any header matching `/^contributor/` or `/^subject/` becomes a list). Fields containing commas are properly `"`-quoted, so `fgetcsv` handles them.
- `collection_id` = **numeric FK → `collections.id`** (replaces the old `book_type` string). Observed values in the CSV: `2` (research, the majority), `4` (e-books), `5` (manuals) — consistent with the id table below.
- `creator` = single main author (name only, no org/email). `contributor1..8` = co-authors (name only). Some rows have no author at all → nullable.
- `subject1..5` = free-text ThaSH-style headings with ` --` subdivisions; **one cell may pack several headings comma-separated** → split needed. No `keyword:`/`ThaSH:`/`DDC:` scheme prefixes.
- `publicsher` = mostly "มหาวิทยาลัยมหาสารคาม"; when it's a faculty/office name it matches the existing **`deps`** table → map to `department_id`, no separate `publishers` table.
- `date` = **stored as-is (พ.ศ. for Thai rows 2541–2569, ค.ศ. for the ~13 English rows 2016–2023)** — keep the source value, do NOT convert.
- `description` = single nullable free-text (Thai grant-acknowledgement line on Thai rows, English on English rows, frequently empty). NOT a th/en pair.
- `identifier` = one fulltext PDF URL per item → single `fulltext_url` column, no rich `item_files` table.
- `degree`, `owner`, `update_at`, `created_at` columns are **empty in every row**.
- Encoding: the attached CSV is still UTF-8-mis-read-as-Latin-1 (mojibake). Import needs a clean **CSV UTF-8** re-save. Some `identifier` URLs contain spaces (need encoding). Near-duplicate rows exist (e.g. 71/72, 132/133, 251–253 by old ID).

**`collection` and `book_type` are ONE axis** (round 6 decision — merged). No `book_types` table, no `book_id`. `categories` (3 fixed groups) → `collections` (13 leaves, `category_id` FK) → `items` (`collection_id` FK, NOT NULL). The 3-category grouping is only for the PublicNavbar dropdown; the browsable detail page is `/collection/{id}` only (no `/category/{id}`). The "ประเภททรัพยากร" filter previously scaffolded in `Collection.vue` is now redundant and should be REMOVED (you're already inside one leaf collection) — keep the sidebar filters: search-within / year / faculty.

**Seed data** — `categories`:
| id | name_en |
|----|---------|
| 1 | INSTITUTIONAL REPOSITORY (MSU-IR) |
| 2 | ARCHIVE AND RARE BOOKS |
| 3 | MULTIMEDIA & E-LEARNING |

`collections` (global id space; `name_th` = the old book_type label where one exists, else null):
| id | name_en | name_th | category_id |
|----|---------|---------|-------------|
| 1 | MSU e-Theses | วิทยานิพนธ์ | 1 |
| 2 | MSU e-Researches | งานวิจัย | 1 |
| 3 | MSU e-Articles | บทความ | 1 |
| 4 | MSU e-Books | หนังสืออิเล็กทรอนิกส์ | 1 |
| 5 | MSU e-Manual | คู่มือ | 1 |
| 6 | Local Wisdom Collection | – | 2 |
| 7 | Rare Books | – | 2 |
| 8 | Manuscripts | – | 2 |
| 9 | Historical Photographs | – | 2 |
| 10 | MSU Multimedia Archives | – | 3 |
| 11 | E-Lecture Series | – | 3 |
| 12 | Research Reports | – | 3 |
| 13 | Free e-Books | – | 3 |

**Finalized schema:**
```
categories    id, name_en, slug, sort_order, timestamps
collections   id, category_id FK→categories, name_en, name_th nullable, slug, sort_order, timestamps
items         id, collection_id FK→collections (NOT NULL), department_id FK→deps nullable (from publicsher),
              owner_id FK→users nullable, title, description nullable,
              year_issued smallint (keep source value, พ.ศ./ค.ศ. mixed), language enum('tha','eng'),
              rights nullable, format default 'pdf', degree nullable (empty now, kept for future),
              fulltext_url, status enum(pending/approved/action_required) default 'approved',
              timestamps + softDeletes
item_titles   id, item_id FK, title, sort_order   (from alternative1/alternative2)
item_person   id, item_id FK→items (cascade), name varchar, role enum(creator/contributor), sort_order,
              index(name), unique(item_id,name,role)   — author NAME stored inline; NO people table
item_subject  id, item_id FK→items (cascade), value varchar, sort_order,
              index(value), unique(item_id,value)      — subject heading stored inline; NO subjects table
```
`item_person` / `item_subject` are plain hasMany child tables (not junctions). `Item::people()` / `creators()` / `contributors()` (filter on `role`) / `subjects()` all `hasMany(...)->orderBy('sort_order')`, via models `App\Models\ItemPerson` / `ItemSubject` (`$table` set explicitly). Author-browse / subject-browse = `SELECT DISTINCT` on those columns (no canonical list; every spelling variant is its own "author"). **User decided 2026-09-01 (round 8): drop the `people` / `subjects` lookup tables — CSV carries only names, no enrichment data, so a lookup table earns nothing here.**

Dropped (no supporting data): `book_types`, `publishers`, `people`, `subjects`, rich `item_files`, org/email/contributor_role on people, `scheme` on subjects, th/en description split, 3-date model, `grantor`.

**Status (2026-09-01) — DB BUILT & DATA IMPORTED (schema round 8: no people/subjects tables).** migrations (`2026_09_01_000001,000002,000005,000006,000007,000008` — note 000003/000004 for people/subjects were DELETED), models (`Category` `Collection` `Item` `ItemTitle` `ItemPerson` `ItemSubject`), `CollectionSeeder`, and `php artisan msuir:import {path} [--fresh]` are written, lint-clean, **and have been run against `msuir_db`**:
- `php artisan migrate` → 6 tables created
- `php artisan db:seed --class=CollectionSeeder` → 3 categories + 13 collections (ids 1–13 pinned, Thai names on the 5 MSU-IR ones)
- `php artisan msuir:import "database/data/elementmsuir.csv" --fresh` → **279 items, 0 skipped** · item_titles 172 · item_person 559 (name inline) · item_subject 919 (value inline). Collection spread: `2`=269, `4`=1, `5`=9. Year range 2010–2569 (พ.ศ./ค.ศ. mixed, kept as-is). 13 eng items. 2 items have no people (CSV rows with blank creator+contributors — allowed).
- The clean source CSV lives at `database/data/elementmsuir.csv` (UTF-8 + BOM; importer skips the BOM). Filename is `elementmsuir.csv`, NOT "Element ข้อมูล MSUIR.csv".

Importer behaviour: header row found by first cell `collection_id`; `contributor`/`subject` columns mapped positionally (`/^contributor/`, `/^subject/`); `date` → digits only, kept as-is; author names + subject headings inserted verbatim as child rows (no lookup, no `firstOrCreate`), deduped only by (name, role) / (value) **within one CSV row**; creator gets `sort_order` 0 then contributors 1,2,3… in file order; whole run in one transaction. Re-run cleanly with `--fresh` (truncates items / item_titles / item_person / item_subject).

**OPEN DECISION — `department_id` is null on every row.** `publicsher` in the CSV is `"คณะศึกษาศาสตร์ มหาวิทยาลัยมหาสารคาม"` (faculty + ` มหาวิทยาลัยมหาสารคาม` suffix, some with a trailing comma) but `deps.name` is the bare `"คณะศึกษาศาสตร์"`, so the exact-match never fires. 253/279 rows are just `"มหาวิทยาลัยมหาสารคาม"` (correctly null). ~26 rows carry a real faculty/office. User was asked whether to upgrade the matcher (strip the ` … มหาวิทยาลัยมหาสารคาม`/comma suffix, then match the leading คณะ/กอง/สำนัก/วิทยาลัย/สถาบัน token against `deps.name`) — **no answer yet.**

**Still TODO (continue 2026-09-02):**
1. Decide the `department_id` matcher question above; if yes, patch `ImportMsuir` and re-run with `--fresh`.
2. Remove the now-redundant `book_type` / `selectedBookTypes` filter from `Collection.vue` + `CollectionController@show` (scaffolded before the merge decision).
3. **Wire `/collection/{id}` to real data** — `CollectionController@show` still returns mock; `Collection.vue` still uses mock props. Build the real query: items in the collection (paginated) + sidebar filters (search-within / year / faculty). This is the largest remaining piece.
4. Nothing committed to git yet (11 modified + new: 6 migrations, models `Category`/`Collection`/`Item`/`ItemTitle`/`ItemPerson`/`ItemSubject`, `CollectionSeeder`, `app/Console/Commands/ImportMsuir.php`, plus the category→collection rename). Commit when the user asks.
