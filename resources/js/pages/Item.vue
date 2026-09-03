<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useLoginGate } from '@/composables/useLoginGate';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// --- Types ---
interface RelatedItem {
    id: number;
    title: string;
    author: string | null;
    year: number | null;
    type: string | null;
}

interface Item {
    id: number;
    title: string;
    title_en?: string | null;
    authors: string[];
    contributors: string[];
    faculty?: string | null;
    year: number | null;
    type: string | null;
    language: string;
    abstract?: string | null;
    keywords: string[];
    rights?: string | null;
    has_fulltext: boolean;
    collection: { id: number; name: string | null };
}

// --- Props (always supplied by ItemController@show) ---
const props = defineProps<{
    item: Item;
    relatedItems: RelatedItem[];
}>();

// --- Page State ---
const activeTab = ref<'file' | 'abstract' | 'citation'>(props.item.abstract ? 'abstract' : 'file');
const showFullAbstract = ref(false);
const copiedCitation = ref<string | null>(null);

const apaCitation = (item: Item) => {
    const authors = item.authors.length ? item.authors.join(', ') : 'มหาวิทยาลัยมหาสารคาม';
    const year = item.year ?? 'ม.ป.ป.';
    const type = item.type ? ` [${item.type}]` : '';
    return `${authors}. (${year}). ${item.title}${type}. มหาวิทยาลัยมหาสารคาม.`;
};

const bibtexCitation = (item: Item) =>
    `@misc{msuir${item.id},\n  author = {${item.authors.join(' and ') || 'Mahasarakham University'}},\n  title  = {${item.title}},\n  school = {Mahasarakham University},\n  year   = {${item.year ?? ''}}\n}`;

const copyCitation = async (type: string, text: string) => {
    await navigator.clipboard.writeText(text);
    copiedCitation.value = type;
    setTimeout(() => (copiedCitation.value = null), 2000);
};

// Full-text is login-gated: block the link for guests, show a message + login modal.
const page = usePage();
const isLoggedIn = computed(() => !!(page.props.auth as { user?: unknown } | undefined)?.user);
const { requireLogin } = useLoginGate();
const onFulltextClick = (e: MouseEvent) => {
    if (!isLoggedIn.value) {
        e.preventDefault();
        requireLogin();
    }
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
                    <li><Link :href="route('collection.show', item.collection.id)" class="font-medium transition-colors text-slate-500 hover:text-blue-800">{{ item.collection.name }}</Link></li>
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
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 space-y-2.5">
                        <a
                            v-if="item.has_fulltext"
                            :href="route('item.download', item.id)"
                            target="_blank"
                            rel="noopener"
                            @click="onFulltextClick"
                            class="flex w-full items-center justify-center gap-2.5 rounded-2xl bg-[#1e3a8a] py-3.5 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition-all hover:bg-blue-800 active:scale-95"
                        >
                            <i class="fas fa-download"></i>
                            เปิด / ดาวน์โหลดเอกสาร
                        </a>
                        <div v-else class="flex w-full items-center justify-center gap-2 py-3.5 text-sm font-bold rounded-2xl bg-slate-100 text-slate-400">
                            <i class="fas fa-ban"></i> ยังไม่มีไฟล์ฉบับเต็ม
                        </div>
                        <p v-if="item.has_fulltext" class="text-center text-[11px] font-bold text-slate-400">
                            <i class="fas fa-lock mr-1 text-[9px]"></i> ต้องเข้าสู่ระบบก่อนดาวน์โหลด
                        </p>
                    </div>

                    <!-- Quick metadata (mobile only shows, desktop always shows) -->
                    <div class="hidden p-5 mt-4 bg-white border shadow-sm rounded-2xl border-slate-100 lg:block">
                        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">ข้อมูลย่อ</p>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-[10px] font-bold uppercase text-slate-400">คอลเลกชัน</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ item.collection.name ?? '—' }}</dd>
                            </div>
                            <div v-if="item.year">
                                <dt class="text-[10px] font-bold uppercase text-slate-400">ปี</dt>
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
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-slate-600">
                                {{ item.collection.name ?? '—' }}
                            </span>
                            <span v-if="item.year" class="rounded-full bg-yellow-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-yellow-800">
                                ปี {{ item.year }}
                            </span>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-[#1e3a8a]">
                                {{ item.language }}
                            </span>
                        </div>

                        <!-- Title TH -->
                        <h1 class="text-xl font-black leading-snug text-slate-900 md:text-2xl">{{ item.title }}</h1>

                        <!-- Title EN -->
                        <p v-if="item.title_en" class="mt-2 text-base italic font-medium leading-snug text-slate-500">{{ item.title_en }}</p>

                        <!-- Authors -->
                        <div v-if="item.authors.length" class="flex flex-wrap gap-2 mt-5">
                            <span
                                v-for="author in item.authors"
                                :key="author"
                                class="flex items-center gap-2 rounded-xl bg-blue-50 px-3.5 py-2 text-sm font-bold text-[#1e3a8a]"
                            >
                                <i class="text-blue-400 fas fa-user-circle"></i>
                                {{ author }}
                            </span>
                        </div>
                    </div>

                    <!-- Metadata Table -->
                    <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-100">
                        <div class="py-5 border-b px-7 border-slate-50">
                            <h2 class="text-[11px] font-black uppercase tracking-widest text-slate-400">ข้อมูลบรรณานุกรม</h2>
                        </div>
                        <dl class="divide-y divide-slate-50">
                            <div v-if="item.contributors.length" class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-user-group mt-0.5 text-slate-300 text-xs"></i>
                                    ผู้แต่งร่วม
                                </dt>
                                <dd class="col-span-3 text-sm text-slate-800">
                                    <div v-for="c in item.contributors" :key="c" class="font-medium">{{ c }}</div>
                                </dd>
                            </div>
                            <div v-if="item.faculty" class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-university mt-0.5 text-slate-300 text-xs"></i>
                                    หน่วยงาน
                                </dt>
                                <dd class="col-span-3 text-sm font-medium text-slate-800">{{ item.faculty }}</dd>
                            </div>
                            <div v-if="item.year" class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-calendar-alt mt-0.5 text-slate-300 text-xs"></i>
                                    ปี
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
                            <div v-if="item.rights" class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-scale-balanced mt-0.5 text-slate-300 text-xs"></i>
                                    สิทธิ์
                                </dt>
                                <dd class="col-span-3 text-sm font-medium text-slate-800">{{ item.rights }}</dd>
                            </div>
                            <!-- Subjects -->
                            <div v-if="item.keywords.length" class="grid grid-cols-5 gap-4 py-4 px-7">
                                <dt class="flex items-start col-span-2 gap-2 text-sm font-bold text-slate-500">
                                    <i class="fas fa-tags mt-0.5 text-slate-300 text-xs"></i>
                                    หัวเรื่อง
                                </dt>
                                <dd class="flex flex-wrap col-span-3 gap-2">
                                    <span
                                        v-for="kw in item.keywords"
                                        :key="kw"
                                        class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600"
                                    >
                                        {{ kw }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- ===== TABS ===== -->
                    <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-100">
                        <!-- Tab Header -->
                        <div class="flex border-b border-slate-100">
                            <button
                                v-for="tab in [{ key: 'abstract', label: 'บทคัดย่อ', icon: 'fa-align-left' }, { key: 'file', label: 'ไฟล์ฉบับเต็ม', icon: 'fa-file-pdf' }, { key: 'citation', label: 'อ้างอิง', icon: 'fa-quote-right' }]"
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

                        <!-- Tab: Abstract -->
                        <div v-if="activeTab === 'abstract'" class="p-6">
                            <template v-if="item.abstract">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="h-5 w-1.5 rounded-full bg-yellow-400"></span>
                                    <h3 class="text-sm font-black tracking-wider uppercase text-slate-700">บทคัดย่อ</h3>
                                </div>
                                <p class="text-sm leading-relaxed whitespace-pre-line text-slate-600" :class="{ 'line-clamp-6': !showFullAbstract }">
                                    {{ item.abstract }}
                                </p>
                                <button @click="showFullAbstract = !showFullAbstract" class="mt-2 text-xs font-bold text-blue-700 transition hover:text-blue-900">
                                    {{ showFullAbstract ? 'ย่อ' : 'อ่านทั้งหมด' }}
                                    <i :class="['fas ml-1 text-[9px]', showFullAbstract ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                </button>
                            </template>
                            <p v-else class="py-6 text-sm text-center text-slate-400">ไม่มีบทคัดย่อสำหรับรายการนี้</p>
                        </div>

                        <!-- Tab: Full text file -->
                        <div v-else-if="activeTab === 'file'" class="p-6">
                            <a
                                v-if="item.has_fulltext"
                                :href="route('item.download', item.id)"
                                target="_blank"
                                rel="noopener"
                                @click="onFulltextClick"
                                class="group flex items-center gap-4 rounded-xl border border-slate-100 bg-white p-4 transition-all hover:border-blue-100 hover:bg-blue-50/30"
                            >
                                <div class="flex items-center justify-center w-12 h-12 text-xl text-red-400 transition shrink-0 rounded-xl bg-red-50 group-hover:bg-red-100">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-800">เอกสารฉบับเต็ม (PDF)</p>
                                    <p class="mt-0.5 text-xs text-slate-400"><i class="fas fa-lock mr-1 text-[9px]"></i> ต้องเข้าสู่ระบบก่อนเปิดไฟล์</p>
                                </div>
                                <span class="flex items-center gap-1.5 rounded-lg bg-[#1e3a8a] px-4 py-2 text-xs font-black text-white shrink-0">
                                    <i class="fas fa-arrow-up-right-from-square text-[10px]"></i> เปิด
                                </span>
                            </a>
                            <p v-else class="py-6 text-sm text-center text-slate-400">ยังไม่มีไฟล์ฉบับเต็ม</p>
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
                                    <p class="mt-1 text-xs text-slate-400">{{ related.author ?? 'ไม่ระบุผู้แต่ง' }}<span v-if="related.year"> · {{ related.year }}</span></p>
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
