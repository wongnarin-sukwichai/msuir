<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// --- Types ---
interface CategoryItem {
    id: number;
    title: string;
    title_en?: string;
    author: string;
    advisor?: string;
    faculty: string;
    year: number;
    type: string;
    abstract: string;
    downloads: number;
    views: number;
}

interface Category {
    id: number;
    name: string;
    name_en: string;
    description: string;
    icon: string;
    total: number;
    slug: string;
}

// --- Props (from Inertia / Laravel) ---
const props = withDefaults(
    defineProps<{
        category?: Category;
        items?: CategoryItem[];
        totalItems?: number;
        currentPage?: number;
        lastPage?: number;
        availableYears?: number[];
        availableFaculties?: string[];
    }>(),
    {
        category: () => ({
            id: 1,
            name: 'MSU e-Theses',
            name_en: 'Theses & Dissertations',
            description: 'วิทยานิพนธ์และดุษฎีนิพนธ์ของนิสิตระดับบัณฑิตศึกษา มหาวิทยาลัยมหาสารคาม รวบรวมผลงานทางวิชาการที่ผ่านการอนุมัติจากคณะกรรมการสอบวิทยานิพนธ์',
            icon: 'fa-graduation-cap',
            total: 12543,
            slug: 'theses',
        }),
        items: () => [
            {
                id: 1,
                title: 'ผลของการใช้กลยุทธ์การเรียนรู้แบบร่วมมือที่มีต่อผลสัมฤทธิ์ทางการเรียนวิชาคณิตศาสตร์ของนักเรียนชั้นมัธยมศึกษาปีที่ 3',
                title_en: 'Effects of Cooperative Learning Strategies on Mathematics Achievement of Grade 9 Students',
                author: 'ศิริลักษณ์ วงศ์ประเสริฐ',
                advisor: 'รศ.ดร.สมชาย ประทุมมาศ',
                faculty: 'คณะศึกษาศาสตร์',
                year: 2567,
                type: 'วิทยานิพนธ์ปริญญาโท',
                abstract:
                    'การวิจัยครั้งนี้มีวัตถุประสงค์เพื่อศึกษาผลของการใช้กลยุทธ์การเรียนรู้แบบร่วมมือที่มีต่อผลสัมฤทธิ์ทางการเรียนวิชาคณิตศาสตร์ กลุ่มตัวอย่างเป็นนักเรียนชั้นมัธยมศึกษาปีที่ 3 โรงเรียนในสังกัดสำนักงานเขตพื้นที่การศึกษา จังหวัดมหาสารคาม',
                downloads: 234,
                views: 1205,
            },
            {
                id: 2,
                title: 'การพัฒนาระบบสารสนเทศเพื่อการจัดการข้อมูลนิสิตในระดับอุดมศึกษา: กรณีศึกษา มหาวิทยาลัยในภาคตะวันออกเฉียงเหนือ',
                title_en: 'Development of Information System for Student Data Management in Higher Education',
                author: 'ธนกร สุวรรณรัตน์',
                advisor: 'ผศ.ดร.วราภรณ์ ชัยมงคล',
                faculty: 'คณะวิทยาการสารสนเทศ',
                year: 2567,
                type: 'วิทยานิพนธ์ปริญญาโท',
                abstract:
                    'การวิจัยนี้มุ่งพัฒนาระบบสารสนเทศที่มีประสิทธิภาพสำหรับการจัดการข้อมูลนิสิตในระดับอุดมศึกษา โดยใช้เทคโนโลยีสมัยใหม่และแนวคิดการออกแบบที่เน้นผู้ใช้เป็นศูนย์กลาง',
                downloads: 189,
                views: 876,
            },
            {
                id: 3,
                title: 'ความหลากหลายทางชีวภาพของพืชสมุนไพรในป่าชุมชนจังหวัดมหาสารคาม',
                title_en: 'Biodiversity of Medicinal Plants in Community Forests of Mahasarakham Province',
                author: 'อรุณี ภูมิไพศาล',
                advisor: 'รศ.ดร.พิชัย สิทธิโชค',
                faculty: 'คณะวิทยาศาสตร์',
                year: 2566,
                type: 'ดุษฎีนิพนธ์ปริญญาเอก',
                abstract:
                    'การศึกษาความหลากหลายทางชีวภาพของพืชสมุนไพรในป่าชุมชนจังหวัดมหาสารคาม ทำการสำรวจและเก็บตัวอย่างพืชในพื้นที่ป่าชุมชน 15 แห่ง พบพืชสมุนไพรจำนวน 312 ชนิด จาก 98 วงศ์',
                downloads: 412,
                views: 2341,
            },
            {
                id: 4,
                title: 'การวิเคราะห์โครงสร้างทางวากยสัมพันธ์ของภาษาไทยถิ่นอีสาน: แนวทางภาษาศาสตร์เชิงประวัติ',
                author: 'มนัสชัย เรืองศรี',
                advisor: 'ศ.ดร.นภาพร จันทรเสนา',
                faculty: 'คณะมนุษยศาสตร์และสังคมศาสตร์',
                year: 2566,
                type: 'ดุษฎีนิพนธ์ปริญญาเอก',
                abstract:
                    'การศึกษาครั้งนี้มุ่งวิเคราะห์โครงสร้างทางวากยสัมพันธ์ของภาษาไทยถิ่นอีสานโดยใช้กรอบแนวคิดทางภาษาศาสตร์เชิงประวัติ เก็บข้อมูลจากชุมชนที่ใช้ภาษาถิ่นอีสานใน 8 จังหวัด',
                downloads: 156,
                views: 743,
            },
            {
                id: 5,
                title: 'ประสิทธิผลของโปรแกรมการดูแลสุขภาพผู้สูงอายุโดยชุมชนมีส่วนร่วม ในเขตอำเภอเมือง จังหวัดมหาสารคาม',
                author: 'จินตนา พลอยสุข',
                advisor: 'รศ.ดร.กิตติศักดิ์ วงศ์สาโรจน์',
                faculty: 'คณะสาธารณสุขศาสตร์',
                year: 2565,
                type: 'วิทยานิพนธ์ปริญญาโท',
                abstract:
                    'การวิจัยเชิงปฏิบัติการครั้งนี้มีวัตถุประสงค์เพื่อศึกษาประสิทธิผลของโปรแกรมการดูแลสุขภาพผู้สูงอายุโดยชุมชนมีส่วนร่วม ซึ่งประกอบด้วยกิจกรรมส่งเสริมสุขภาพ การตรวจคัดกรองโรค และการติดตามดูแล',
                downloads: 298,
                views: 1567,
            },
        ],
        totalItems: 12543,
        currentPage: 1,
        lastPage: 1255,
        availableYears: () => [2567, 2566, 2565, 2564, 2563, 2562, 2561, 2560],
        availableFaculties: () => [
            'คณะศึกษาศาสตร์',
            'คณะวิทยาการสารสนเทศ',
            'คณะวิทยาศาสตร์',
            'คณะมนุษยศาสตร์และสังคมศาสตร์',
            'คณะสาธารณสุขศาสตร์',
            'คณะวิศวกรรมศาสตร์',
            'คณะการบัญชีและการจัดการ',
            'คณะแพทยศาสตร์',
            'คณะพยาบาลศาสตร์',
            'คณะศิลปกรรมศาสตร์',
            'คณะนิติศาสตร์',
            'คณะสถาปัตยกรรมศาสตร์',
        ],
    },
);

// --- Filter State ---
const searchQuery = ref('');
const selectedYears = ref<number[]>([]);
const selectedFaculties = ref<string[]>([]);
const sortBy = ref<'date' | 'title' | 'downloads' | 'views'>('date');
const viewMode = ref<'list' | 'grid'>('list');
const isYearOpen = ref(true);
const isFacultyOpen = ref(true);
const isMobileFilterOpen = ref(false);

const sortOptions = [
    { value: 'date', label: 'วันที่ล่าสุด' },
    { value: 'title', label: 'ชื่อเรื่อง ก-ฮ' },
    { value: 'downloads', label: 'ดาวน์โหลดมากสุด' },
    { value: 'views', label: 'เข้าชมมากสุด' },
];

const typeStyle: Record<string, string> = {
    'วิทยานิพนธ์ปริญญาโท': 'bg-blue-100 text-blue-800',
    'ดุษฎีนิพนธ์ปริญญาเอก': 'bg-violet-100 text-violet-800',
    IS: 'bg-emerald-100 text-emerald-800',
    'Senior Project': 'bg-orange-100 text-orange-800',
};

const toggleYear = (year: number) => {
    const idx = selectedYears.value.indexOf(year);
    if (idx === -1) selectedYears.value.push(year);
    else selectedYears.value.splice(idx, 1);
};

const toggleFaculty = (faculty: string) => {
    const idx = selectedFaculties.value.indexOf(faculty);
    if (idx === -1) selectedFaculties.value.push(faculty);
    else selectedFaculties.value.splice(idx, 1);
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedYears.value = [];
    selectedFaculties.value = [];
};

const hasActiveFilters = computed(() => searchQuery.value || selectedYears.value.length > 0 || selectedFaculties.value.length > 0);

const activeFilterCount = computed(() => selectedYears.value.length + selectedFaculties.value.length + (searchQuery.value ? 1 : 0));
</script>

<template>
    <PublicLayout>
    <Head :title="`${category.name} | MSU Institutional Repository (MSU IR)`" />

        <!-- ===== BREADCRUMB ===== -->
        <div class="bg-white border-b border-slate-100">
            <div class="px-4 py-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <ol class="flex items-center space-x-2 text-sm">
                    <li>
                        <a href="/" class="font-medium transition-colors text-slate-500 hover:text-blue-800">
                            <i class="fas fa-home mr-1.5 text-xs"></i>หน้าแรก
                        </a>
                    </li>
                    <li><i class="fas fa-chevron-right text-[9px] text-slate-300"></i></li>
                    <li>
                        <a href="#" class="font-medium transition-colors text-slate-500 hover:text-blue-800">คอลเลกชัน</a>
                    </li>
                    <li><i class="fas fa-chevron-right text-[9px] text-slate-300"></i></li>
                    <li class="font-bold text-[#1e3a8a]">{{ category.name }}</li>
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
                <i :class="['fas absolute -right-8 -bottom-8 text-[20rem]', category.icon]"></i>
            </div>

            <div class="relative z-10 px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-5">
                        <div class="flex items-center justify-center w-16 h-16 text-white shrink-0 rounded-2xl bg-white/10 backdrop-blur-sm ring-1 ring-white/20">
                            <i :class="['fas text-2xl', category.icon]"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <span class="rounded-full bg-yellow-400/20 px-3 py-0.5 text-[11px] font-black uppercase tracking-widest text-yellow-300">
                                    MSU-IR Collection
                                </span>
                            </div>
                            <h1 class="text-3xl font-black text-white md:text-4xl">{{ category.name }}</h1>
                            <p class="mt-1 text-sm font-medium text-blue-200/80">{{ category.name_en }}</p>
                            <p class="max-w-xl mt-3 text-sm leading-relaxed text-blue-100/70">{{ category.description }}</p>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="flex items-center gap-8 p-6 shrink-0 rounded-2xl bg-white/10 backdrop-blur-sm ring-1 ring-white/20 md:flex-col md:items-start md:gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-blue-300">รายการทั้งหมด</p>
                            <p class="text-4xl font-black text-white">{{ totalItems?.toLocaleString() }}</p>
                        </div>
                        <div class="h-8 w-[1px] bg-white/20 md:hidden"></div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-blue-300">อัปเดตล่าสุด</p>
                            <p class="text-sm font-bold text-white">พฤษภาคม 2568</p>
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
                    แสดง <span class="text-[#1e3a8a]">{{ items?.length }}</span> จาก {{ totalItems?.toLocaleString() }} รายการ
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
                        <!-- Search within category -->
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
                                        v-for="year in availableYears"
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
                                        <span class="text-xs text-slate-400">{{ Math.floor(Math.random() * 500 + 100) }}</span>
                                    </label>
                                </div>
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
                            พบ <span class="text-[#1e3a8a]">{{ totalItems?.toLocaleString() }}</span> รายการ
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
                        <article
                            v-for="item in items"
                            :key="item.id"
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
                                    <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide', typeStyle[item.type] ?? 'bg-slate-100 text-slate-600']">
                                        {{ item.type }}
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-500">
                                        ปี {{ item.year }}
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-500 hidden md:inline">
                                        {{ item.faculty }}
                                    </span>
                                </div>

                                <!-- Title -->
                                <h2 class="text-base font-bold leading-snug text-slate-900 transition-colors line-clamp-2 group-hover:text-[#1e3a8a]">
                                    {{ item.title }}
                                </h2>
                                <p v-if="item.title_en" class="mt-0.5 text-xs font-medium text-slate-400 italic line-clamp-1">{{ item.title_en }}</p>

                                <!-- Metadata -->
                                <div class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span class="flex items-center gap-1.5">
                                        <i class="fas fa-user text-slate-300"></i>
                                        {{ item.author }}
                                    </span>
                                    <span v-if="item.advisor" class="flex items-center gap-1.5">
                                        <i class="fas fa-user-tie text-slate-300"></i>
                                        {{ item.advisor }}
                                    </span>
                                </div>

                                <!-- Abstract -->
                                <p class="mt-3 text-sm leading-relaxed text-slate-500 line-clamp-2">{{ item.abstract }}</p>

                                <!-- Footer row -->
                                <div class="flex items-center justify-between mt-4">
                                    <div class="flex items-center gap-5 text-xs text-slate-400">
                                        <span class="flex items-center gap-1.5">
                                            <i class="fas fa-download text-slate-300"></i>
                                            {{ item.downloads.toLocaleString() }} ครั้ง
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <i class="fas fa-eye text-slate-300"></i>
                                            {{ item.views.toLocaleString() }} ครั้ง
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a]">
                                            <i class="fas fa-info-circle text-[10px]"></i>
                                            รายละเอียด
                                        </button>
                                        <button class="flex items-center gap-1.5 rounded-lg bg-[#1e3a8a] px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-800 active:scale-95">
                                            <i class="fas fa-download text-[10px]"></i>
                                            ดาวน์โหลด
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- GRID VIEW -->
                    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <article
                            v-for="item in items"
                            :key="item.id"
                            class="group flex flex-col rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-100 hover:shadow-md cursor-pointer"
                        >
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex items-center justify-center w-10 h-12 transition shrink-0 rounded-xl bg-slate-50 text-slate-300 ring-1 ring-slate-100 group-hover:bg-blue-50 group-hover:text-blue-400">
                                    <i class="text-xl fas fa-file-pdf"></i>
                                </div>
                                <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide', typeStyle[item.type] ?? 'bg-slate-100 text-slate-600']">
                                    {{ item.type }}
                                </span>
                            </div>
                            <h2 class="flex-1 text-sm font-bold leading-snug text-slate-900 transition-colors line-clamp-3 group-hover:text-[#1e3a8a]">{{ item.title }}</h2>
                            <div class="flex flex-wrap mt-3 text-xs gap-x-3 gap-y-1 text-slate-400">
                                <span>{{ item.author }}</span>
                                <span class="text-slate-200">|</span>
                                <span>ปี {{ item.year }}</span>
                            </div>
                            <div class="flex items-center justify-between pt-4 mt-4 border-t border-slate-50">
                                <span class="text-xs text-slate-400"><i class="mr-1 fas fa-download text-slate-300"></i>{{ item.downloads }}</span>
                                <button class="flex items-center gap-1.5 rounded-lg bg-[#1e3a8a] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-blue-800 active:scale-95">
                                    <i class="fas fa-external-link-alt text-[9px]"></i>
                                    ดูรายละเอียด
                                </button>
                            </div>
                        </article>
                    </div>

                    <!-- ===== PAGINATION ===== -->
                    <div class="flex flex-col items-center gap-4 mt-8 sm:flex-row sm:justify-between">
                        <p class="text-sm text-slate-500">
                            หน้า <span class="font-bold text-slate-800">{{ currentPage }}</span> จาก
                            <span class="font-bold text-slate-800">{{ lastPage?.toLocaleString() }}</span>
                        </p>

                        <div class="flex items-center gap-2">
                            <button
                                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a] disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="currentPage === 1"
                            >
                                <i class="text-xs fas fa-chevron-left"></i>
                            </button>

                            <template v-for="p in [1, 2, 3, '...', lastPage]" :key="p">
                                <span v-if="p === '...'" class="px-1 text-sm text-slate-400">•••</span>
                                <button
                                    v-else
                                    :class="[
                                        'flex h-9 w-9 items-center justify-center rounded-xl border text-sm font-bold transition',
                                        p === currentPage
                                            ? 'border-[#1e3a8a] bg-[#1e3a8a] text-white shadow-sm'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a]',
                                    ]"
                                >
                                    {{ p }}
                                </button>
                            </template>

                            <button
                                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a] disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="currentPage === lastPage"
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
