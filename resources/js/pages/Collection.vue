<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

// --- Types ---
interface CollectionItem {
    id: number;
    title: string;
    title_en?: string | null;
    author?: string | null;
    faculty?: string | null;
    year?: number | null;
    language: 'tha' | 'eng';
    abstract?: string | null;
}

interface CollectionInfo {
    id: number;
    name: string;
    name_en: string;
    category?: string | null;
    description?: string | null;
    icon: string;
}

interface Pagination {
    total: number;
    currentPage: number;
    lastPage: number;
    perPage: number;
}

interface Filters {
    q: string;
    years: number[];
    faculties: string[];
    sort: 'date' | 'title';
}

// --- Props (always supplied by CollectionController@show) ---
const props = defineProps<{
    collection: CollectionInfo;
    items: CollectionItem[];
    pagination: Pagination;
    filters: Filters;
    availableYears: number[];
    availableFaculties: string[];
}>();

// --- Filter state (seeded from the URL via props.filters) ---
const searchQuery = ref(props.filters.q);
const selectedYears = ref<number[]>([...props.filters.years]);
const selectedFaculties = ref<string[]>([...props.filters.faculties]);
const sortBy = ref<'date' | 'title'>(props.filters.sort);
const viewMode = ref<'list' | 'grid'>('list');
const isYearOpen = ref(true);
const isFacultyOpen = ref(true);
const isMobileFilterOpen = ref(false);

// Year filter: show the 5 most recent by default, expand for the rest.
const YEAR_PREVIEW = 5;
const isYearExpanded = ref(false);
const visibleYears = computed(() =>
    isYearExpanded.value ? props.availableYears : props.availableYears.slice(0, YEAR_PREVIEW),
);

const sortOptions = [
    { value: 'date', label: 'ปีล่าสุด' },
    { value: 'title', label: 'ชื่อเรื่อง ก-ฮ' },
];

// --- Server round-trip: push the current filter state into the URL ---
function applyFilters(extra: Record<string, unknown> = {}) {
    router.get(
        route('collection.show', props.collection.id),
        {
            q: searchQuery.value || undefined,
            years: selectedYears.value.length ? selectedYears.value : undefined,
            faculties: selectedFaculties.value.length ? selectedFaculties.value : undefined,
            sort: sortBy.value !== 'date' ? sortBy.value : undefined,
            ...extra,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(searchQuery, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 350);
});
watch(sortBy, () => applyFilters());

const toggleYear = (year: number) => {
    const idx = selectedYears.value.indexOf(year);
    if (idx === -1) selectedYears.value.push(year);
    else selectedYears.value.splice(idx, 1);
    applyFilters();
};

const toggleFaculty = (faculty: string) => {
    const idx = selectedFaculties.value.indexOf(faculty);
    if (idx === -1) selectedFaculties.value.push(faculty);
    else selectedFaculties.value.splice(idx, 1);
    applyFilters();
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedYears.value = [];
    selectedFaculties.value = [];
    applyFilters();
};

const hasActiveFilters = computed(
    () => searchQuery.value || selectedYears.value.length > 0 || selectedFaculties.value.length > 0,
);

const activeFilterCount = computed(
    () => selectedYears.value.length + selectedFaculties.value.length + (searchQuery.value ? 1 : 0),
);

// --- Pagination ---
const goToPage = (page: number) => {
    if (page < 1 || page > props.pagination.lastPage || page === props.pagination.currentPage) return;
    applyFilters({ page });
};

const pageNumbers = computed<(number | '...')[]>(() => {
    const { currentPage: c, lastPage: last } = props.pagination;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const out: (number | '...')[] = [1];
    const from = Math.max(2, c - 1);
    const to = Math.min(last - 1, c + 1);
    if (from > 2) out.push('...');
    for (let p = from; p <= to; p++) out.push(p);
    if (to < last - 1) out.push('...');
    out.push(last);
    return out;
});
</script>

<template>
    <PublicLayout>
    <Head :title="`${collection.name} | MSU Institutional Repository (MSU IR)`" />

        <!-- ===== BREADCRUMB ===== -->
        <div class="bg-white border-b border-slate-100">
            <div class="px-4 py-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <ol class="flex items-center space-x-2 text-sm">
                    <li>
                        <Link href="/" class="font-medium transition-colors text-slate-500 hover:text-blue-800">
                            <i class="fas fa-home mr-1.5 text-xs"></i>หน้าแรก
                        </Link>
                    </li>
                    <li><i class="fas fa-chevron-right text-[9px] text-slate-300"></i></li>
                    <li>
                        <a href="#" class="font-medium transition-colors text-slate-500 hover:text-blue-800">คอลเลกชัน</a>
                    </li>
                    <li><i class="fas fa-chevron-right text-[9px] text-slate-300"></i></li>
                    <li class="font-bold text-[#1e3a8a]">{{ collection.name }}</li>
                </ol>
            </div>
        </div>

        <!-- ===== CATEGORY BANNER ===== -->
        <div class="relative overflow-hidden bg-[#1e3a8a]">
            <!-- Background decoration -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-white blur-[80px]"></div>
                <div class="absolute -bottom-32 left-1/3 h-64 w-64 rounded-full bg-yellow-300 blur-[80px]"></div>
            </div>
            <div class="absolute inset-0 opacity-5">
                <i :class="['fas absolute -right-8 -bottom-8 text-[20rem]', collection.icon]"></i>
            </div>

            <div class="relative z-10 px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-5">
                        <div class="flex items-center justify-center w-16 h-16 text-white shrink-0 rounded-2xl bg-white/10 backdrop-blur-sm ring-1 ring-white/20">
                            <i :class="['fas text-2xl', collection.icon]"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <span class="rounded-full bg-yellow-400/20 px-3 py-0.5 text-[11px] font-black uppercase tracking-widest text-yellow-300">
                                    MSU-IR Collection
                                </span>
                            </div>
                            <h1 class="text-3xl font-black text-white md:text-4xl">{{ collection.name }}</h1>
                            <p class="mt-1 text-sm font-medium text-blue-200/80">{{ collection.name_en }}</p>
                            <p v-if="collection.description" class="max-w-xl mt-3 text-sm leading-relaxed text-blue-100/70">{{ collection.description }}</p>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="flex items-center gap-8 p-6 shrink-0 rounded-2xl bg-white/10 backdrop-blur-sm ring-1 ring-white/20 md:flex-col md:items-start md:gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-blue-300">รายการทั้งหมด</p>
                            <p class="text-4xl font-black text-white">{{ pagination.total.toLocaleString() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== MAIN CONTENT ===== -->
        <div class="px-4 py-8 mx-auto max-w-7xl sm:px-6 lg:px-8">

            <!-- Mobile: Filter toggle button -->
            <div class="flex items-center justify-between mb-4 lg:hidden">
                <p class="text-sm font-bold text-slate-700">
                    แสดง <span class="text-[#1e3a8a]">{{ items.length }}</span> จาก {{ pagination.total.toLocaleString() }} รายการ
                </p>
                <button
                    @click="isMobileFilterOpen = !isMobileFilterOpen"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-bold transition bg-white border shadow-sm rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50"
                >
                    <i class="fas fa-sliders-h text-[#1e3a8a]"></i>
                    กรอง / ค้นหา
                    <span v-if="activeFilterCount > 0" class="flex h-5 w-5 items-center justify-center rounded-full bg-yellow-400 text-[10px] font-black text-slate-900">
                        {{ activeFilterCount }}
                    </span>
                </button>
            </div>

            <div class="flex gap-8">

                <!-- ===== SIDEBAR ===== -->
                <aside
                    :class="[
                        'w-72 shrink-0',
                        'lg:block',
                        isMobileFilterOpen ? 'fixed inset-0 z-50 overflow-y-auto bg-white p-6 pt-20 lg:relative lg:inset-auto lg:z-auto lg:overflow-visible lg:bg-transparent lg:p-0 lg:pt-0' : 'hidden',
                    ]"
                >
                    <!-- Mobile close button -->
                    <div class="flex items-center justify-between mb-6 lg:hidden">
                        <h3 class="text-lg font-black text-slate-900">กรองผลลัพธ์</h3>
                        <button @click="isMobileFilterOpen = false" class="flex items-center justify-center rounded-full h-9 w-9 bg-slate-100 text-slate-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Search within collection -->
                        <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-100">
                            <div class="p-5">
                                <h3 class="mb-3 text-[11px] font-black uppercase tracking-widest text-slate-400">ค้นหาในหมวดนี้</h3>
                                <div class="relative">
                                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="ชื่อเรื่อง, ผู้แต่ง..."
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-300 focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Active filters -->
                        <div v-if="hasActiveFilters" class="p-4 border border-yellow-200 rounded-2xl bg-yellow-50">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-black uppercase tracking-widest text-yellow-700">ตัวกรองที่ใช้งาน</span>
                                <button @click="clearFilters" class="text-[11px] font-bold text-yellow-700 underline transition hover:text-red-600">
                                    ล้างทั้งหมด
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span v-if="searchQuery" class="flex items-center gap-1 px-3 py-1 text-xs font-bold text-yellow-900 bg-yellow-200 rounded-full">
                                    "{{ searchQuery }}"
                                    <button @click="searchQuery = ''" class="ml-0.5 hover:text-red-600"><i class="fas fa-times text-[9px]"></i></button>
                                </span>
                                <span v-for="y in selectedYears" :key="y" class="flex items-center gap-1 px-3 py-1 text-xs font-bold text-yellow-900 bg-yellow-200 rounded-full">
                                    ปี {{ y }}
                                    <button @click="toggleYear(y)" class="ml-0.5 hover:text-red-600"><i class="fas fa-times text-[9px]"></i></button>
                                </span>
                                <span v-for="f in selectedFaculties" :key="f" class="flex items-center gap-1 px-3 py-1 text-xs font-bold text-yellow-900 bg-yellow-200 rounded-full">
                                    {{ f.replace('คณะ', '') }}
                                    <button @click="toggleFaculty(f)" class="ml-0.5 hover:text-red-600"><i class="fas fa-times text-[9px]"></i></button>
                                </span>
                            </div>
                        </div>

                        <!-- Filter: Year -->
                        <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-100">
                            <button
                                @click="isYearOpen = !isYearOpen"
                                class="flex items-center justify-between w-full p-5 transition hover:bg-slate-50"
                            >
                                <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">ปีการศึกษา</span>
                                <i :class="['fas fa-chevron-down text-[10px] text-slate-400 transition-transform', isYearOpen ? 'rotate-180' : '']"></i>
                            </button>
                            <div v-if="isYearOpen" class="px-5 pt-3 pb-5 border-t border-slate-50">
                                <div class="space-y-2.5">
                                    <label
                                        v-for="year in visibleYears"
                                        :key="year"
                                        class="flex items-center justify-between cursor-pointer group"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                @click="toggleYear(year)"
                                                :class="[
                                                    'flex h-4 w-4 shrink-0 items-center justify-center rounded border transition',
                                                    selectedYears.includes(year) ? 'border-[#1e3a8a] bg-[#1e3a8a]' : 'border-slate-300 group-hover:border-blue-400',
                                                ]"
                                            >
                                                <i v-if="selectedYears.includes(year)" class="fas fa-check text-[8px] text-white"></i>
                                            </div>
                                            <span class="text-sm font-medium text-slate-700 group-hover:text-[#1e3a8a]" @click="toggleYear(year)">{{ year }}</span>
                                        </div>
                                    </label>
                                </div>
                                <button
                                    v-if="availableYears.length > YEAR_PREVIEW"
                                    @click="isYearExpanded = !isYearExpanded"
                                    class="mt-3 text-[11px] font-black text-[#1e3a8a] hover:text-blue-800"
                                >
                                    {{ isYearExpanded ? 'ย่อ' : `แสดงอีก ${availableYears.length - YEAR_PREVIEW} ปี` }}
                                    <i :class="['fas ml-1 text-[8px]', isYearExpanded ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Filter: Faculty -->
                        <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-100">
                            <button
                                @click="isFacultyOpen = !isFacultyOpen"
                                class="flex items-center justify-between w-full p-5 transition hover:bg-slate-50"
                            >
                                <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">คณะ / หน่วยงาน</span>
                                <i :class="['fas fa-chevron-down text-[10px] text-slate-400 transition-transform', isFacultyOpen ? 'rotate-180' : '']"></i>
                            </button>
                            <div v-if="isFacultyOpen" class="px-5 pt-3 pb-5 border-t border-slate-50">
                                <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1 custom-scrollbar">
                                    <label
                                        v-for="faculty in availableFaculties"
                                        :key="faculty"
                                        class="flex items-start gap-3 cursor-pointer group"
                                    >
                                        <div
                                            @click="toggleFaculty(faculty)"
                                            :class="[
                                                'mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border transition',
                                                selectedFaculties.includes(faculty) ? 'border-[#1e3a8a] bg-[#1e3a8a]' : 'border-slate-300 group-hover:border-blue-400',
                                            ]"
                                        >
                                            <i v-if="selectedFaculties.includes(faculty)" class="fas fa-check text-[8px] text-white"></i>
                                        </div>
                                        <span class="text-sm font-medium leading-tight text-slate-700 group-hover:text-[#1e3a8a]" @click="toggleFaculty(faculty)">{{ faculty }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile apply button -->
                        <button
                            @click="isMobileFilterOpen = false"
                            class="w-full rounded-2xl bg-[#1e3a8a] py-3.5 text-sm font-black text-white shadow-lg transition hover:bg-blue-800 active:scale-95 lg:hidden"
                        >
                            แสดงผลลัพธ์
                        </button>
                    </div>
                </aside>

                <!-- ===== ITEMS AREA ===== -->
                <div class="flex-1 min-w-0">
                    <!-- Sort & View Controls -->
                    <div class="flex flex-col gap-3 p-4 mb-5 bg-white border shadow-sm rounded-2xl border-slate-100 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm font-bold text-slate-600">
                            พบ <span class="text-[#1e3a8a]">{{ pagination.total.toLocaleString() }}</span> รายการ
                        </p>
                        <div class="flex items-center gap-3">
                            <!-- Sort -->
                            <div class="flex items-center gap-2">
                                <label class="hidden text-xs font-bold text-slate-400 sm:block">เรียงตาม</label>
                                <select
                                    v-model="sortBy"
                                    class="px-3 py-2 text-sm font-bold border rounded-xl border-slate-200 bg-slate-50 text-slate-700 focus:border-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-400/20"
                                >
                                    <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </div>
                            <!-- View mode -->
                            <div class="flex overflow-hidden border rounded-xl border-slate-200">
                                <button
                                    @click="viewMode = 'list'"
                                    :class="['px-3 py-2 text-sm transition', viewMode === 'list' ? 'bg-[#1e3a8a] text-white' : 'bg-white text-slate-400 hover:bg-slate-50']"
                                >
                                    <i class="fas fa-list"></i>
                                </button>
                                <button
                                    @click="viewMode = 'grid'"
                                    :class="['px-3 py-2 text-sm transition border-l border-slate-200', viewMode === 'grid' ? 'bg-[#1e3a8a] text-white' : 'bg-white text-slate-400 hover:bg-slate-50']"
                                >
                                    <i class="fas fa-th-large"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- LIST VIEW -->
                    <div v-if="viewMode === 'list'" class="space-y-4">
                        <Link
                            v-for="item in items"
                            :key="item.id"
                            :href="route('item.show', item.id)"
                            class="group flex gap-5 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-100 hover:shadow-md cursor-pointer"
                        >
                            <!-- Thumbnail / Icon -->
                            <div class="flex-col items-center justify-center hidden w-16 h-24 gap-1 transition sm:flex shrink-0 rounded-xl bg-slate-50 text-slate-300 ring-1 ring-slate-100 group-hover:bg-blue-50 group-hover:text-blue-400 group-hover:ring-blue-100">
                                <i class="text-3xl fas fa-file-pdf"></i>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <!-- Badges row -->
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide text-slate-600">
                                        {{ item.language === 'eng' ? 'ENG' : 'ไทย' }}
                                    </span>
                                    <span v-if="item.year" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-500">
                                        ปี {{ item.year }}
                                    </span>
                                    <span v-if="item.faculty" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-500 hidden md:inline">
                                        {{ item.faculty }}
                                    </span>
                                </div>

                                <!-- Title -->
                                <h2 class="text-base font-bold leading-snug text-slate-900 transition-colors line-clamp-2 group-hover:text-[#1e3a8a]">
                                    {{ item.title }}
                                </h2>
                                <p v-if="item.title_en" class="mt-0.5 text-xs font-medium text-slate-400 italic line-clamp-1">{{ item.title_en }}</p>

                                <!-- Metadata -->
                                <div v-if="item.author" class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span class="flex items-center gap-1.5">
                                        <i class="fas fa-user text-slate-300"></i>
                                        {{ item.author }}
                                    </span>
                                </div>

                                <!-- Abstract -->
                                <p v-if="item.abstract" class="mt-3 text-sm leading-relaxed text-slate-500 line-clamp-2">{{ item.abstract }}</p>

                                <!-- Footer row -->
                                <div class="flex items-center justify-end mt-4">
                                    <div class="flex items-center gap-2">
                                        <span class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a]">
                                            <i class="fas fa-info-circle text-[10px]"></i>
                                            รายละเอียด
                                        </span>
                                        <button @click.prevent class="flex items-center gap-1.5 rounded-lg bg-[#1e3a8a] px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-800 active:scale-95">
                                            <i class="fas fa-download text-[10px]"></i>
                                            ดาวน์โหลด
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- GRID VIEW -->
                    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <Link
                            v-for="item in items"
                            :key="item.id"
                            :href="route('item.show', item.id)"
                            class="group flex flex-col rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-100 hover:shadow-md cursor-pointer"
                        >
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex items-center justify-center w-10 h-12 transition shrink-0 rounded-xl bg-slate-50 text-slate-300 ring-1 ring-slate-100 group-hover:bg-blue-50 group-hover:text-blue-400">
                                    <i class="text-xl fas fa-file-pdf"></i>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide text-slate-600">
                                    {{ item.language === 'eng' ? 'ENG' : 'ไทย' }}
                                </span>
                            </div>
                            <h2 class="flex-1 text-sm font-bold leading-snug text-slate-900 transition-colors line-clamp-3 group-hover:text-[#1e3a8a]">{{ item.title }}</h2>
                            <div class="flex flex-wrap mt-3 text-xs gap-x-3 gap-y-1 text-slate-400">
                                <span v-if="item.author">{{ item.author }}</span>
                                <span v-if="item.author && item.year" class="text-slate-200">|</span>
                                <span v-if="item.year">ปี {{ item.year }}</span>
                            </div>
                            <div class="flex items-center justify-end pt-4 mt-4 border-t border-slate-50">
                                <span class="flex items-center gap-1.5 rounded-lg bg-[#1e3a8a] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-blue-800">
                                    <i class="fas fa-external-link-alt text-[9px]"></i>
                                    ดูรายละเอียด
                                </span>
                            </div>
                        </Link>
                    </div>

                    <!-- EMPTY STATE -->
                    <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-20 text-center bg-white border shadow-sm rounded-2xl border-slate-100">
                        <div class="flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-slate-50 text-slate-300">
                            <i class="text-2xl fas fa-inbox"></i>
                        </div>
                        <p class="text-base font-bold text-slate-700">ไม่พบรายการที่ตรงกับเงื่อนไข</p>
                        <button v-if="hasActiveFilters" @click="clearFilters" class="mt-3 text-sm font-bold text-[#1e3a8a] underline hover:text-blue-800">
                            ล้างตัวกรอง
                        </button>
                    </div>

                    <!-- ===== PAGINATION ===== -->
                    <div v-if="pagination.lastPage > 1" class="flex flex-col items-center gap-4 mt-8 sm:flex-row sm:justify-between">
                        <p class="text-sm text-slate-500">
                            หน้า <span class="font-bold text-slate-800">{{ pagination.currentPage }}</span> จาก
                            <span class="font-bold text-slate-800">{{ pagination.lastPage.toLocaleString() }}</span>
                        </p>

                        <div class="flex items-center gap-2">
                            <button
                                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a] disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="pagination.currentPage === 1"
                                @click="goToPage(pagination.currentPage - 1)"
                            >
                                <i class="text-xs fas fa-chevron-left"></i>
                            </button>

                            <template v-for="(p, i) in pageNumbers" :key="i">
                                <span v-if="p === '...'" class="px-1 text-sm text-slate-400">•••</span>
                                <button
                                    v-else
                                    @click="goToPage(p)"
                                    :class="[
                                        'flex h-9 w-9 items-center justify-center rounded-xl border text-sm font-bold transition',
                                        p === pagination.currentPage
                                            ? 'border-[#1e3a8a] bg-[#1e3a8a] text-white shadow-sm'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a]',
                                    ]"
                                >
                                    {{ p }}
                                </button>
                            </template>

                            <button
                                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a] disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="pagination.currentPage === pagination.lastPage"
                                @click="goToPage(pagination.currentPage + 1)"
                            >
                                <i class="text-xs fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== FOOTER ===== -->
        <section class="pt-16 pb-10 mt-12 bg-white border-t border-slate-200">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-12 mb-12 md:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-5">
                        <div class="flex items-center space-x-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#1e3a8a] text-sm font-black text-white">M</div>
                            <span class="text-xl font-black text-slate-900">MSU IR</span>
                        </div>
                        <p class="text-sm leading-relaxed text-slate-500">คลังทรัพยากรดิจิทัลเพื่อการวิจัยและส่งเสริมความรู้ของท้องถิ่น มุ่งเน้นการแบ่งปันความรู้สู่ระดับสากล</p>
                        <div class="flex space-x-4">
                            <i class="text-lg transition-colors cursor-pointer fab fa-facebook text-slate-300 hover:text-blue-600"></i>
                            <i class="text-lg transition-colors cursor-pointer fab fa-twitter text-slate-300 hover:text-blue-400"></i>
                            <i class="text-lg transition-colors cursor-pointer fas fa-envelope text-slate-300 hover:text-red-500"></i>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">มหาวิทยาลัย</h4>
                        <ul class="space-y-4">
                            <li v-for="link in [{ label: 'เว็บไซต์มหาวิทยาลัย', href: 'https://msu.ac.th/' }, { label: 'กองทะเบียนและประมวลผล', href: 'https://regpr.msu.ac.th/' }, { label: 'บัณฑิตวิทยาลัย', href: 'https://grad.msu.ac.th/' }]" :key="link.label">
                                <a :href="link.href" target="_blank" class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    {{ link.label }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">ห้องสมุดและวิจัย</h4>
                        <ul class="space-y-4">
                            <li v-for="link in [{ label: 'สำนักวิทยบริการ', href: 'https://library.msu.ac.th/' }, { label: 'ระบบสืบค้น WebOPAC', href: 'https://opac.msu.ac.th/' }, { label: 'ฐานข้อมูลออนไลน์', href: 'https://library.msu.ac.th/?page_id=1437' }]" :key="link.label">
                                <a :href="link.href" target="_blank" class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    {{ link.label }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">ช่วยเหลือ</h4>
                        <ul class="space-y-4">
                            <li v-for="label in ['คู่มือการใช้งาน', 'คำถามที่พบบ่อย (FAQ)', 'แจ้งปัญหาการใช้งาน']" :key="label">
                                <a href="#" class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    {{ label }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="pt-8 text-center border-t border-slate-200">
                    <p class="text-[10px] font-black uppercase leading-loose tracking-[0.4em] text-slate-400">
                        © 2026 MAHA SARAKHAM UNIVERSITY | INSTITUTIONAL REPOSITORY
                    </p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 99px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }
</style>
