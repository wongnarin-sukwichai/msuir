# MSU IR — Institutional Repository (Mahasarakham University)

Laravel 12 + Inertia.js v2 + Vue 3 (TypeScript) + Tailwind CSS. XAMPP/MySQL locally, DB name `msuir_db`.

## Conventions established so far

- **Admin backend is one file**: `resources/js/pages/Dashboard.vue` — sidebar + all tabs (dashboard/repository/approvals/analytics/members/settings) live in this single SFC via `activeTab` ref, not separate routed pages. No shared `AppSidebarLayout` is used here (that layout exists in `resources/js/layouts/app/` but is currently disconnected/unused).
- **Public pages** use `resources/js/layouts/PublicLayout.vue` (Welcome, Category, Item, Tutorial) — wraps `PublicNavbar`, `LoginModal`, `CookieBanner`, `ImpersonationBanner`.
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

**Not yet implemented** — still in design/analysis phase, no migrations written yet.

Two real Google Sheets/records were reviewed (see conversation ~2026-08-07):
1. A flat "Element ฐานข้อมูล MSUIR" sheet — simplified Dublin Core column headers (title, creator, subject, description, publisher, contributor, date, type, format, identifier, source, language, coverage, right, degree, owner, book_type, created_at, update_at)
2. Five sample **full record pages** (from a different university's live IR site, ubru.ac.th — used only as a structural reference, not MSU content) that revealed the real shape is much richer than the flat sheet: repeated Creator blocks (Name+Organization+Email), Subject entries typed as `keyword:`/`ThaSH:`/`Classification::DDC:`, Publisher with Name+Address+Email, three separate dates (Created/Modified/Issued, in พ.ศ.), Contributor with free-text Role, a Thesis/Grantor field (thesis-only), and a per-file table with access-count + last-accessed stats.

**Schema decided so far** (pending final confirmation once real CSV data is seen):
```
categories     -- self-referencing (parent_id) 2-level tree: group (e.g. "INSTITUTIONAL REPOSITORY (MSU-IR)")
                  > leaf collection (e.g. "MSU e-Theses"). Replaces collectionGroups mock in PublicNavbar.vue.
items          -- category_id (FK, single collection per item — confirmed), owner_id (FK users.id — uploader,
                  backend-only, confirmed appropriate), book_type (independent dimension from category — confirmed),
                  title, title_alternative, description_th, description_en, type, format, identifier, source,
                  language, coverage_spatial, rights, degree, grantor, date_created, date_issued,
                  publisher_id (FK), status (pending/approved/action_required — ties into existing approvals tab)
publishers     -- name, address, email (dedup table, same pattern as deps)
item_people    -- item_id, role(creator/contributor), name, organization, email, contributor_role (free text)
item_subjects  -- item_id, scheme(keyword/thash/ddc), value
item_files     -- item_id, file_name, file_path, size_bytes, access_count, last_accessed_at, is_restricted
```

**⚠️ BLOCKED — waiting on user.** User is preparing a **CSV export** of the real data (said to be more complete/unambiguous than the screenshot samples). Explicitly asked to be reminded about this next time they bring the topic up.
**When resuming this thread**: ask whether the CSV is ready before doing anything else on items/categories. Once received, still need answers to: (1) whether the 5 sample records were real content or just structural reference (confirm against CSV), (2) Buddhist-era vs Gregorian date storage, (3) whether description is always a Thai+English pair or sometimes single-language. Do not write migrations for this feature until the CSV is reviewed.
