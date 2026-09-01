<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

// --- Types ---
interface FileAttachment {
    id: number;
    name: string;
    size: string;
    pages?: number;
    type: 'pdf' | 'epub' | 'video';
    restricted: boolean;
}

interface RelatedItem {
    id: number;
    title: string;
    author: string;
    year: number;
    type: string;
}

interface Item {
    id: number;
    title: string;
    title_en?: string;
    authors: string[];
    advisors?: string[];
    faculty: string;
    department?: string;
    year: number;
    type: string;
    language: string;
    abstract: string;
    abstract_en?: string;
    keywords: string[];
    doi?: string;
    isbn?: string;
    views: number;
    downloads: number;
    files: FileAttachment[];
    collection: { name: string; slug: string };
}

// --- Props ---
const props = withDefaults(
    defineProps<{
        item?: Item;
        relatedItems?: RelatedItem[];
    }>(),
    {
        item: () => ({
            id: 1,
            title: 'ผลของการใช้กลยุทธ์การเรียนรู้แบบร่วมมือที่มีต่อผลสัมฤทธิ์ทางการเรียนวิชาคณิตศาสตร์ของนักเรียนชั้นมัธยมศึกษาปีที่ 3',
            title_en: 'Effects of Cooperative Learning Strategies on Mathematics Achievement of Grade 9 Students',
            authors: ['ศิริลักษณ์ วงศ์ประเสริฐ'],
            advisors: ['รศ.ดร.สมชาย ประทุมมาศ', 'ผศ.ดร.กัลยา วานิชวัฒนา'],
            faculty: 'คณะศึกษาศาสตร์',
            department: 'สาขาวิชาหลักสูตรและการสอน',
            year: 2567,
            type: 'วิทยานิพนธ์ปริญญาโท',
            language: 'ภาษาไทย',
            abstract:
                'การวิจัยครั้งนี้มีวัตถุประสงค์เพื่อ 1) ศึกษาผลของการใช้กลยุทธ์การเรียนรู้แบบร่วมมือที่มีต่อผลสัมฤทธิ์ทางการเรียนวิชาคณิตศาสตร์ของนักเรียนชั้นมัธยมศึกษาปีที่ 3 และ 2) ศึกษาความพึงพอใจของนักเรียนที่มีต่อการจัดการเรียนรู้แบบร่วมมือ กลุ่มตัวอย่างเป็นนักเรียนชั้นมัธยมศึกษาปีที่ 3 โรงเรียนในสังกัดสำนักงานเขตพื้นที่การศึกษามัธยมศึกษา จังหวัดมหาสารคาม ภาคเรียนที่ 1 ปีการศึกษา 2567 จำนวน 60 คน แบ่งออกเป็นกลุ่มทดลอง 30 คน และกลุ่มควบคุม 30 คน เครื่องมือที่ใช้ในการวิจัย ได้แก่ แผนการจัดการเรียนรู้แบบร่วมมือ แบบทดสอบวัดผลสัมฤทธิ์ทางการเรียน และแบบสอบถามความพึงพอใจ',
            abstract_en:
                'This research aimed to 1) study the effects of cooperative learning strategies on mathematics achievement of Grade 9 students, and 2) investigate students\' satisfaction with cooperative learning. The sample consisted of 60 Grade 9 students from schools under the Secondary Educational Service Area Office in Mahasarakham Province, divided into 30 experimental and 30 control group students.',
            keywords: ['การเรียนรู้แบบร่วมมือ', 'ผลสัมฤทธิ์ทางการเรียน', 'คณิตศาสตร์', 'มัธยมศึกษา', 'Cooperative Learning'],
            doi: '10.14456/msuir.2567.001',
            views: 1205,
            downloads: 234,
            files: [
                { id: 1, name: 'THESIS_SIRILAK_2567.pdf', size: '4.2 MB', pages: 182, type: 'pdf', restricted: false },
                { id: 2, name: 'ABSTRACT_SIRILAK_2567.pdf', size: '0.5 MB', pages: 4, type: 'pdf', restricted: false },
                { id: 3, name: 'APPENDIX_SIRILAK_2567.pdf', size: '1.8 MB', pages: 45, type: 'pdf', restricted: true },
            ],
            collection: { name: 'MSU e-Theses', slug: 'theses' },
        }),
        relatedItems: () => [
            { id: 2, title: 'การพัฒนาชุดกิจกรรมการเรียนรู้แบบโครงงานเพื่อส่งเสริมทักษะการคิดเชิงสร้างสรรค์', author: 'ธนกร สุวรรณรัตน์', year: 2567, type: 'วิทยานิพนธ์ปริญญาโท' },
            { id: 3, title: 'ผลการใช้รูปแบบการสอนแบบสืบเสาะหาความรู้ต่อผลสัมฤทธิ์ทางการเรียนวิทยาศาสตร์', author: 'อรุณี ภูมิไพศาล', year: 2566, type: 'วิทยานิพนธ์ปริญญาโท' },
            { id: 4, title: 'การพัฒนาทักษะการคิดวิจารณญาณของนักเรียนผ่านกิจกรรมการเรียนรู้เชิงรุก', author: 'มนัสชัย เรืองศรี', year: 2566, type: 'ดุษฎีนิพนธ์ปริญญาเอก' },
            { id: 5, title: 'ประสิทธิผลของสื่อดิจิทัลเชิงโต้ตอบในการเรียนการสอนระดับมัธยมศึกษา', author: 'จินตนา พลอยสุข', year: 2565, type: 'วิทยานิพนธ์ปริญญาโท' },
        ],
    },
);


// --- Page State ---
const activeTab = ref<'files' | 'abstract' | 'citation'>('files');
const showFullAbstract = ref(false);
const showFullAbstractEn = ref(false);
const copiedCitation = ref<string | null>(null);
const isBookmarked = ref(false);

const typeStyle: Record<string, string> = {
    'วิทยานิพนธ์ปริญญาโท': 'bg-blue-100 text-blue-800',
    'ดุษฎีนิพนธ์ปริญญาเอก': 'bg-violet-100 text-violet-800',
    'IS': 'bg-emerald-100 text-emerald-800',
    'Senior Project': 'bg-orange-100 text-orange-800',
};

const fileIcon: Record<string, string> = {
    pdf: 'fa-file-pdf',
    epub: 'fa-book-open',
    video: 'fa-film',
};

const fileColor: Record<string, string> = {
    pdf: 'text-red-400 bg-red-50 group-hover:bg-red-100',
    epub: 'text-blue-400 bg-blue-50 group-hover:bg-blue-100',
    video: 'text-violet-400 bg-violet-50 group-hover:bg-violet-100',
};

const apaCitation = (item: Item) =>
    `${item.authors.join(', ')}. (${item.year}). ${item.title} [${item.type}]. มหาวิทยาลัยมหาสารคาม. https://doi.org/${item.doi}`;

const bibtexCitation = (item: Item) =>
    `@mastersthesis{msuir${item.id},\n  author  = {${item.authors.join(' and ')}},\n  title   = {${item.title}},\n  school  = {Mahasarakham University},\n  year    = {${item.year}},\n  doi     = {${item.doi}}\n}`;

const copyCitation = async (type: string, text: string) => {
    await navigator.clipboard.writeText(text);
    copiedCitation.value = type;
    setTimeout(() => (copiedCitation.value = null), 2000);
};
</script>

<template>
    <PublicLayout>
    <Head :title="`${item.title.slice(0, 60)}... | MSU Institutional Repository (MSU IR)`" />

        <!-- ===== BREADCRUMB ===== -->
        <div class="bg-white border-b border-slate-100">
            <div class="px-4 py-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <ol class="flex flex-wrap items-center gap-2 text-sm">
                    <li><Link href="/" class="font-medium transition-colors text-slate-500 hover:text-blue-800"><i class="fas fa-home mr-1.5 text-xs"></i>หน้าแรก</Link></li>
                    <li><i class="fas fa-chevron-right text-[9px] text-slate-300"></i></li>
                    <li><Link :href="route('collection.show', 1)" class="font-medium transition-colors text-slate-500 hover:text-blue-800">{{ item.collection.name }}</Link></li>
                    <li><i class="fas fa-chevron-right text-[9px] text-slate-300"></i></li>
                    <li class="max-w-xs truncate font-bold text-[#1e3a8a]">{{ item.title.slice(0, 50) }}...</li>
                </ol>
            </div>
        </div>

        <!-- ===== MAIN ===== -->
        <div class="px-4 py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-start">

                <!-- ===== LEFT PANEL ===== -->
                <aside class="w-full shrink-0 lg:w-64 xl:w-72">

                    <!-- Cover -->
                    <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-100">
                        <div class="flex aspect-[3/4] items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100">
                            <div class="flex flex-col items-center gap-3 text-slate-300">
                                <i class="fas fa-file-pdf text-7xl"></i>
                                <span class="text-xs font-bold tracking-widest uppercase">PDF Document</span>
                            </div>
                        </div>

                        <!-- Stats bar -->
                        <div class="grid grid-cols-2 border-t divide-x divide-slate-100 border-slate-100">
                            <div class="flex flex-col items-center py-4">
                                <span class="text-2xl font-black text-[#1e3a8a]">{{ item.downloads.toLocaleString() }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">ดาวน์โหลด</span>
                            </div>
                            <div class="flex flex-col items-center py-4">
                                <span class="text-2xl font-black text-[#1e3a8a]">{{ item.views.toLocaleString() }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">เข้าชม</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 space-y-2.5">
                        <button class="flex w-full items-center justify-center gap-2.5 rounded-2xl bg-[#1e3a8a] py-3.5 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition-all hover:bg-blue-800 active:scale-95">
                            <i class="fas fa-download"></i>
                            ดาวน์โหลดเอกสาร
                        </button>
                        <button class="flex w-full items-center justify-center gap-2.5 rounded-2xl border border-slate-200 bg-white py-3 text-sm font-bold text-slate-700 transition-all hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a]">
                            <i class="fas fa-eye text-slate-400"></i>
                            อ่านออนไลน์
                        </button>
                        <div class="flex gap-2">
                            <button
                                @click="isBookmarked = !isBookmarked"
                                :class="['flex flex-1 items-center justify-center gap-2 rounded-xl border py-2.5 text-sm font-bold transition-all', isBookmarked ? 'border-yellow-300 bg-yellow-50 text-yellow-700' : 'border-slate-200 bg-white text-slate-600 hover:border-yellow-200 hover:bg-yellow-50 hover:text-yellow-700']"
                            >
                                <i :class="['fas', isBookmarked ? 'fa-bookmark' : 'fa-bookmark']"></i>
                                {{ isBookmarked ? 'บันทึกแล้ว' : 'บันทึก' }}
                            </button>
                            <button class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white py-2.5 text-sm font-bold text-slate-600 transition-all hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a]">
                                <i class="fas fa-share-alt"></i>
                                แชร์
                            </button>
                        </div>
                    </div>

                    <!-- DOI -->
                    <div v-if="item.doi" class="p-4 mt-4 bg-white border shadow-sm rounded-2xl border-slate-100">
                        <p class="mb-1.5 text-[10px] font-black uppercase tracking-widest text-slate-400">DOI</p>
                        <a :href="`https://doi.org/${item.doi}`" target="_blank" class="text-xs font-medium text-blue-700 underline break-all hover:text-blue-900">
                            {{ item.doi }}
                        </a>
                    </div>

                    <!-- Quick metadata (mobile only shows, desktop always shows) -->
                    <div class="hidden p-5 mt-4 bg-white border shadow-sm rounded-2xl border-slate-100 lg:block">
                        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">ข้อมูลย่อ</p>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-[10px] font-bold uppercase text-slate-400">ประเภท</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ item.type }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold uppercase text-slate-400">ปีการศึกษา</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ item.year }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold uppercase text-slate-400">ภาษา</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ item.language }}</dd>
                            </div>
                        </dl>
                    </div>
                </aside>

                <!-- ===== RIGHT PANEL ===== -->
                <div class="flex-1 min-w-0 space-y-5">

                    <!-- Title Card -->
                    <div class="bg-white border shadow-sm rounded-2xl border-slate-100 p-7">
                        <!-- Badges -->
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            <span :class="['rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-wide', typeStyle[item.type] ?? 'bg-slate-100 text-slate-600']">
                                {{ item.type }}
                            </span>
                            <span class="rounded-full bg-yellow-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-yellow-800">
                                ปี {{ item.year }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600">
                                {{ item.collection.name }}
                            </span>
                        </div>

                        <!-- Title TH -->
                        <h1 class="text-xl font-black leading-snug text-slate-900 md:text-2xl">{{ item.title }}</h1>

                        <!-- Title EN -->
                        <p v-if="item.title_en" class="mt-2 text-base italic font-medium leading-snug text-slate-500">{{ item.title_en }}</p>

                        <!-- Authors -->
                        <div class="flex flex-wrap gap-2 mt-5">
                            <a
                                v-for="author in item.authors"
                                :key="author"
                                href="#"
                                class="flex items-center gap-2 rounded-xl bg-blue-50 px-3.5 py-2 text-sm font-bold text-[#1e3a8a] transition hover:bg-blue-100"
                            >
                                <i class="text-blue-400 fas fa-user-circle"></i>
                                {{ author }}
                            </a>
                        </div>
                    </div>

                    <!-- Metadata Table -->
                    <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-100">
                        <div class="py-5 border-b px-7 border-slate-50">
                            <h2 class="text-[11px] font-black uppercase tracking-widest text-slate-400">ข้อมูลบรรณานุกรม</h2>
                        </div>
                        <dl class="divide-y divide-slate-50">
                            <div v-if="item.advisors?.length" class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-user-tie mt-0.5 text-slate-300 text-xs"></i>
                                    อาจารย์ที่ปรึกษา
                                </dt>
                                <dd class="col-span-3 text-sm text-slate-800">
                                    <div v-for="advisor in item.advisors" :key="advisor">
                                        <a href="#" class="font-medium transition-colors hover:text-[#1e3a8a]">{{ advisor }}</a>
                                    </div>
                                </dd>
                            </div>
                            <div class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-university mt-0.5 text-slate-300 text-xs"></i>
                                    คณะ / หน่วยงาน
                                </dt>
                                <dd class="col-span-3 text-sm text-slate-800">
                                    <a href="#" class="font-medium transition-colors hover:text-[#1e3a8a]">{{ item.faculty }}</a>
                                    <span v-if="item.department" class="block text-slate-500">{{ item.department }}</span>
                                </dd>
                            </div>
                            <div class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-calendar-alt mt-0.5 text-slate-300 text-xs"></i>
                                    ปีการศึกษา
                                </dt>
                                <dd class="col-span-3 text-sm font-medium text-slate-800">{{ item.year }}</dd>
                            </div>
                            <div class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-language mt-0.5 text-slate-300 text-xs"></i>
                                    ภาษา
                                </dt>
                                <dd class="col-span-3 text-sm font-medium text-slate-800">{{ item.language }}</dd>
                            </div>
                            <div v-if="item.doi" class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-link mt-0.5 text-slate-300 text-xs"></i>
                                    DOI
                                </dt>
                                <dd class="col-span-3">
                                    <a :href="`https://doi.org/${item.doi}`" target="_blank" class="text-sm font-medium text-blue-700 underline break-all hover:text-blue-900">
                                        {{ item.doi }}
                                    </a>
                                </dd>
                            </div>
                            <!-- Keywords -->
                            <div v-if="item.keywords.length" class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-tags mt-0.5 text-slate-300 text-xs"></i>
                                    คำสำคัญ
                                </dt>
                                <dd class="flex flex-wrap col-span-3 gap-2">
                                    <a
                                        v-for="kw in item.keywords"
                                        :key="kw"
                                        href="#"
                                        class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 transition hover:border-yellow-300 hover:bg-yellow-50 hover:text-yellow-800"
                                    >
                                        {{ kw }}
                                    </a>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- ===== TABS ===== -->
                    <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-100">
                        <!-- Tab Header -->
                        <div class="flex border-b border-slate-100">
                            <button
                                v-for="tab in [{ key: 'files', label: 'ไฟล์ดิจิทัล', icon: 'fa-folder-open' }, { key: 'abstract', label: 'บทคัดย่อ', icon: 'fa-align-left' }, { key: 'citation', label: 'อ้างอิง', icon: 'fa-quote-right' }]"
                                :key="tab.key"
                                @click="activeTab = tab.key as any"
                                :class="[
                                    'flex items-center gap-2 px-6 py-4 text-sm font-bold transition-all border-b-2',
                                    activeTab === tab.key ? 'border-[#1e3a8a] text-[#1e3a8a]' : 'border-transparent text-slate-500 hover:text-slate-800',
                                ]"
                            >
                                <i :class="['fas text-xs', tab.icon]"></i>
                                {{ tab.label }}
                            </button>
                        </div>

                        <!-- Tab: Files -->
                        <div v-if="activeTab === 'files'" class="p-6">
                            <div class="space-y-3">
                                <div
                                    v-for="file in item.files"
                                    :key="file.id"
                                    :class="['group flex items-center gap-4 rounded-xl border p-4 transition-all', file.restricted ? 'border-slate-100 bg-slate-50/50' : 'border-slate-100 bg-white hover:border-blue-100 hover:bg-blue-50/30 cursor-pointer']"
                                >
                                    <!-- File icon -->
                                    <div :class="['flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl transition', fileColor[file.type]]">
                                        <i :class="['fas', fileIcon[file.type]]"></i>
                                    </div>

                                    <!-- File info -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold truncate text-slate-800">{{ file.name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">
                                            {{ file.size }}
                                            <span v-if="file.pages"> · {{ file.pages }} หน้า</span>
                                        </p>
                                    </div>

                                    <!-- Action -->
                                    <div class="shrink-0">
                                        <div v-if="file.restricted" class="flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-400">
                                            <i class="fas fa-lock text-[10px]"></i>
                                            ต้องเข้าสู่ระบบ
                                        </div>
                                        <button v-else class="flex items-center gap-1.5 rounded-lg bg-[#1e3a8a] px-4 py-2 text-xs font-black text-white shadow-sm transition hover:bg-blue-800 active:scale-95">
                                            <i class="fas fa-download text-[10px]"></i>
                                            ดาวน์โหลด
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-4 text-xs text-center text-slate-400">
                                <i class="mr-1 fas fa-shield-alt"></i>
                                ไฟล์ที่มีสัญลักษณ์กุญแจต้องเข้าสู่ระบบด้วยบัญชี @msu.ac.th
                            </p>
                        </div>

                        <!-- Tab: Abstract -->
                        <div v-else-if="activeTab === 'abstract'" class="p-6 space-y-6">
                            <!-- Thai abstract -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="h-5 w-1.5 rounded-full bg-yellow-400"></span>
                                    <h3 class="text-sm font-black tracking-wider uppercase text-slate-700">บทคัดย่อ (ภาษาไทย)</h3>
                                </div>
                                <p class="text-sm leading-relaxed text-slate-600" :class="{ 'line-clamp-5': !showFullAbstract }">
                                    {{ item.abstract }}
                                </p>
                                <button @click="showFullAbstract = !showFullAbstract" class="mt-2 text-xs font-bold text-blue-700 transition hover:text-blue-900">
                                    {{ showFullAbstract ? 'ย่อ' : 'อ่านทั้งหมด' }}
                                    <i :class="['fas ml-1 text-[9px]', showFullAbstract ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                </button>
                            </div>

                            <!-- English abstract -->
                            <div v-if="item.abstract_en" class="pt-6 border-t border-slate-50">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="h-5 w-1.5 rounded-full bg-blue-400"></span>
                                    <h3 class="text-sm font-black tracking-wider uppercase text-slate-700">Abstract (English)</h3>
                                </div>
                                <p class="text-sm italic leading-relaxed text-slate-600" :class="{ 'line-clamp-5': !showFullAbstractEn }">
                                    {{ item.abstract_en }}
                                </p>
                                <button @click="showFullAbstractEn = !showFullAbstractEn" class="mt-2 text-xs font-bold text-blue-700 transition hover:text-blue-900">
                                    {{ showFullAbstractEn ? 'Show less' : 'Read more' }}
                                    <i :class="['fas ml-1 text-[9px]', showFullAbstractEn ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Tab: Citation -->
                        <div v-else-if="activeTab === 'citation'" class="p-6 space-y-4">
                            <!-- APA -->
                            <div class="p-4 border rounded-xl border-slate-100 bg-slate-50">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">APA Style</span>
                                    <button
                                        @click="copyCitation('apa', apaCitation(item))"
                                        :class="['flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition', copiedCitation === 'apa' ? 'bg-green-100 text-green-700' : 'bg-white border border-slate-200 text-slate-600 hover:border-blue-200 hover:text-[#1e3a8a]']"
                                    >
                                        <i :class="['fas text-[10px]', copiedCitation === 'apa' ? 'fa-check' : 'fa-copy']"></i>
                                        {{ copiedCitation === 'apa' ? 'คัดลอกแล้ว!' : 'คัดลอก' }}
                                    </button>
                                </div>
                                <p class="font-mono text-xs leading-relaxed text-slate-700">{{ apaCitation(item) }}</p>
                            </div>

                            <!-- BibTeX -->
                            <div class="p-4 border rounded-xl border-slate-100 bg-slate-50">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">BibTeX</span>
                                    <button
                                        @click="copyCitation('bibtex', bibtexCitation(item))"
                                        :class="['flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition', copiedCitation === 'bibtex' ? 'bg-green-100 text-green-700' : 'bg-white border border-slate-200 text-slate-600 hover:border-blue-200 hover:text-[#1e3a8a]']"
                                    >
                                        <i :class="['fas text-[10px]', copiedCitation === 'bibtex' ? 'fa-check' : 'fa-copy']"></i>
                                        {{ copiedCitation === 'bibtex' ? 'คัดลอกแล้ว!' : 'คัดลอก' }}
                                    </button>
                                </div>
                                <pre class="font-mono text-xs leading-relaxed whitespace-pre-wrap text-slate-700">{{ bibtexCitation(item) }}</pre>
                            </div>

                            <!-- Export buttons -->
                            <div class="flex flex-wrap gap-2 pt-2">
                                <button class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a]">
                                    <i class="fas fa-file-export text-slate-400"></i>
                                    ส่งออก EndNote
                                </button>
                                <button class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a]">
                                    <i class="fas fa-file-export text-slate-400"></i>
                                    ส่งออก Zotero
                                </button>
                                <button class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a]">
                                    <i class="fas fa-print text-slate-400"></i>
                                    พิมพ์
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ===== RELATED ITEMS ===== -->
                    <div v-if="relatedItems?.length" class="p-6 bg-white border shadow-sm rounded-2xl border-slate-100">
                        <div class="flex items-center gap-3 mb-5">
                            <span class="h-6 w-1.5 rounded-full bg-yellow-400"></span>
                            <h2 class="text-base font-black text-slate-900">ผลงานที่เกี่ยวข้อง</h2>
                        </div>
                        <div class="space-y-3">
                            <Link
                                v-for="related in relatedItems"
                                :key="related.id"
                                :href="route('item.show', related.id)"
                                class="flex items-start gap-4 p-4 transition-all border group rounded-xl border-slate-100 hover:border-blue-100 hover:bg-blue-50/30"
                            >
                                <div class="flex items-center justify-center w-8 h-10 transition rounded-lg shrink-0 bg-slate-100 text-slate-300 group-hover:bg-blue-100 group-hover:text-blue-400">
                                    <i class="text-sm fas fa-file-pdf"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold leading-snug text-slate-800 line-clamp-2 transition group-hover:text-[#1e3a8a]">{{ related.title }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ related.author }} · {{ related.year }}</p>
                                </div>
                                <i class="mt-1 text-xs transition fas fa-chevron-right text-slate-200 group-hover:text-blue-400 shrink-0"></i>
                            </Link>
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
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>{{ link.label }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">ห้องสมุดและวิจัย</h4>
                        <ul class="space-y-4">
                            <li v-for="link in [{ label: 'สำนักวิทยบริการ', href: 'https://library.msu.ac.th/' }, { label: 'ระบบสืบค้น WebOPAC', href: 'https://opac.msu.ac.th/' }, { label: 'ฐานข้อมูลออนไลน์', href: 'https://library.msu.ac.th/?page_id=1437' }]" :key="link.label">
                                <a :href="link.href" target="_blank" class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>{{ link.label }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">ช่วยเหลือ</h4>
                        <ul class="space-y-4">
                            <li v-for="label in ['คู่มือการใช้งาน', 'คำถามที่พบบ่อย (FAQ)', 'แจ้งปัญหาการใช้งาน']" :key="label">
                                <a href="#" class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>{{ label }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="pt-8 text-center border-t border-slate-200">
                    <p class="text-[10px] font-black uppercase leading-loose tracking-[0.4em] text-slate-400">© 2026 MAHA SARAKHAM UNIVERSITY | INSTITUTIONAL REPOSITORY</p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }
</style>
