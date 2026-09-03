<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface HomeCollection { id: number; name: string; name_en: string; count: number }
interface HomeItem { id: number; title: string; author: string | null; faculty: string | null; collection: string | null; year: number | null }

const props = defineProps<{
    collections: HomeCollection[];
    recommended: HomeItem[];
    newReleases: HomeItem[];
    stats: { total: number; byCategory: { name: string; count: number }[] };
}>();

// Category card background: real file at /public/images/collections/{id}.jpg,
// otherwise fall back to a random placeholder so a missing file never shows broken.
const catImage = (cat: HomeCollection) => `/images/collections/${cat.id}.png`;
const onCatImageError = (e: Event, idx: number) => {
    const img = e.target as HTMLImageElement;
    if (img.dataset.fallback) return;
    img.dataset.fallback = '1';
    img.src = `https://picsum.photos/seed/${idx + 71}/700/600`;
};

const catScroller = ref<HTMLElement | null>(null);
const scrollCats = (dir: 1 | -1) => {
    const el = catScroller.value;
    if (!el) return;
    // one card + gap ≈ 340 + 24
    el.scrollBy({ left: dir * 364, behavior: 'smooth' });
};

const searchTerm = ref('');
const submitSearch = () => {
    const q = searchTerm.value.trim();
    if (!q) return;
    // No global search yet — send to the largest MSU-IR collection with the term.
    const target = props.collections.slice().sort((a, b) => b.count - a.count)[0]?.id ?? 2;
    router.get(route('collection.show', target), { q });
};
</script>

<template>
    <PublicLayout>
    <Head title="หน้าแรก | MSU Institutional Repository (MSU IR)" />

        <header class="relative px-4 py-24 overflow-hidden bg-white">
            <div class="absolute inset-0 opacity-40">
                <div class="absolute -left-24 -top-24 h-96 w-96 rounded-full bg-yellow-100 blur-[100px]"></div>
                <div class="absolute -bottom-24 -right-24 h-96 w-96 rounded-full bg-blue-100 blur-[100px]"></div>
            </div>

            <div class="relative z-10 max-w-4xl mx-auto text-center">
                <h1 class="mb-6 text-4xl font-black tracking-tight text-slate-900 md:text-5xl">
                    คลังปัญญา <span class="text-[#1e3a8a]">MSU</span> <span class="text-yellow-500">IR</span>
                </h1>
                <p class="max-w-2xl mx-auto mb-10 text-lg leading-relaxed text-slate-500">
                    ศูนย์รวมวิทยานิพนธ์ ผลงานวิจัย และทรัพยากรสารสนเทศดิจิทัล<br class="hidden md:block" />
                    จากมหาวิทยาลัยมหาสารคาม
                </p>

                <form @submit.prevent="submitSearch" class="relative max-w-3xl mx-auto group">
                    <div class="absolute inset-y-0 flex items-center pointer-events-none left-6">
                        <i class="transition-colors fas fa-search text-slate-400 group-focus-within:text-yellow-500"></i>
                    </div>
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="ค้นหาชื่อเรื่อง, ผู้แต่ง, หัวข้อวิจัย..."
                        class="w-full py-5 text-lg transition-all bg-white border-2 shadow-xl rounded-2xl border-slate-100 px-14 text-slate-900 shadow-slate-200/50 placeholder:text-slate-400 focus:border-yellow-400 focus:outline-none focus:ring-4 focus:ring-yellow-400/10"
                    />
                    <button
                        type="submit"
                        class="absolute right-3 top-3 rounded-xl bg-[#1e3a8a] px-8 py-2.5 font-bold text-white shadow-lg transition-all hover:bg-blue-800 active:scale-95"
                    >
                        สืบค้น
                    </button>
                </form>
            </div>
        </header>

        <main class="px-4 py-20 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <section class="mb-24">
                <div class="flex items-center justify-between mb-10">
                    <div class="flex items-center space-x-4">
                        <div class="h-10 w-1.5 rounded-full bg-yellow-400"></div>
                        <h2 class="text-3xl font-black tracking-tight text-slate-900">หมวดหมู่ทรัพยากร</h2>
                    </div>
                    <div class="items-center hidden gap-2 sm:flex">
                        <button
                            @click="scrollCats(-1)"
                            class="flex items-center justify-center transition border rounded-full h-11 w-11 border-slate-200 bg-white text-slate-600 hover:border-blue-300 hover:text-[#1e3a8a] active:scale-95"
                            aria-label="ก่อนหน้า"
                        >
                            <i class="text-sm fas fa-chevron-left"></i>
                        </button>
                        <button
                            @click="scrollCats(1)"
                            class="flex items-center justify-center transition border rounded-full h-11 w-11 border-slate-200 bg-white text-slate-600 hover:border-blue-300 hover:text-[#1e3a8a] active:scale-95"
                            aria-label="ถัดไป"
                        >
                            <i class="text-sm fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div
                    ref="catScroller"
                    class="flex gap-6 px-1 pb-3 -mx-1 overflow-x-auto cat-slider snap-x snap-mandatory scroll-smooth"
                >
                    <Link
                        v-for="(cat, idx) in collections"
                        :key="cat.id"
                        :href="route('collection.show', cat.id)"
                        class="shrink-0 snap-start w-[280px] sm:w-[340px] shadow-lg cursor-pointer collection-card group h-72 bg-slate-200"
                    >
                        <img :src="catImage(cat)" @error="onCatImageError($event, idx)" class="object-cover w-full h-full" :alt="cat.name" />
                        <div class="absolute inset-0 flex flex-col justify-end p-8 collection-overlay">
                            <h3 class="mb-1 text-2xl font-bold text-white">{{ cat.name }}</h3>
                            <p class="text-sm font-medium text-blue-100">{{ cat.name_en }}</p>
                            <p class="mt-1 text-xs font-bold text-yellow-300">{{ cat.count.toLocaleString() }} รายการ</p>
                            <div class="w-0 h-1 mt-3 transition-all duration-500 bg-yellow-400 rounded-full group-hover:w-full"></div>
                        </div>
                    </Link>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-12 lg:grid-cols-4">
                <div class="space-y-16 lg:col-span-3">
                    <section>
                        <div class="flex items-end justify-between mb-4">
                            <h2 class="flex items-center mb-4 text-2xl font-bold text-slate-900">
                                <span class="flex items-center justify-center w-10 h-10 mr-4 text-yellow-600 bg-yellow-100 rounded-xl">
                                    <i class="fas fa-star"></i>
                                </span>
                                Recommended
                            </h2>
                            <button class="text-sm font-bold text-blue-800 transition-colors hover:text-yellow-600">ดูทั้งหมด</button>
                        </div>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <Link
                                v-for="rItem in recommended"
                                :key="rItem.id"
                                :href="route('item.show', rItem.id)"
                                class="block p-6 transition-all bg-white border cursor-pointer card-shadow group rounded-3xl border-slate-100"
                            >
                                <div class="flex items-start">
                                    <div
                                        class="flex items-center justify-center w-16 h-20 transition-colors shrink-0 rounded-xl bg-slate-50 text-slate-300 group-hover:bg-blue-50 group-hover:text-blue-600"
                                    >
                                        <i class="text-3xl fas fa-file-pdf"></i>
                                    </div>
                                    <div class="ml-5">
                                        <h3 class="font-bold leading-snug transition-colors line-clamp-2 text-slate-900 group-hover:text-blue-800">
                                            {{ rItem.title }}
                                        </h3>
                                        <p class="mt-2 text-xs text-slate-500">{{ rItem.author ?? 'ไม่ระบุผู้แต่ง' }}</p>
                                    </div>
                                </div>
                            </Link>
                            <p v-if="recommended.length === 0" class="text-sm text-slate-400">ยังไม่มีรายการ</p>
                        </div>
                    </section>

                    <!-- 1.2 New Release Section -->
                    <section>
                        <div class="flex items-center mb-4">
                            <h2 class="flex items-center text-2xl font-bold text-slate-900">
                                <span class="flex items-center justify-center w-10 h-10 mr-4 text-blue-700 bg-blue-200 rounded-xl">
                                    <i class="fas fa-plus"></i>
                                </span>
                                New Release
                            </h2>
                        </div>
                        <div class="divide-y divide-slate-50 overflow-hidden rounded-[2rem] border border-slate-100 bg-white shadow-sm">
                            <Link v-for="nItem in newReleases" :key="nItem.id" :href="route('item.show', nItem.id)" class="flex items-center p-6 transition-colors cursor-pointer group hover:bg-slate-50">
                                <div
                                    class="flex items-center justify-center w-10 h-10 mr-6 transition-colors rounded-lg shrink-0 bg-slate-100 text-slate-400 group-hover:bg-blue-200 group-hover:text-blue-700"
                                >
                                    <i class="text-sm fas fa-clock"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3
                                        class="text-base font-bold tracking-tight truncate transition-colors text-slate-800 group-hover:text-blue-900"
                                    >
                                        {{ nItem.title }}
                                    </h3>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ nItem.collection ?? '—' }}<span v-if="nItem.faculty"> · {{ nItem.faculty }}</span><span v-if="nItem.year"> · {{ nItem.year }}</span>
                                    </p>
                                </div>
                                <i class="ml-4 transition-colors fas fa-chevron-right text-slate-200 group-hover:text-blue-900"></i>
                            </Link>
                            <p v-if="newReleases.length === 0" class="p-6 text-sm text-slate-400">ยังไม่มีรายการ</p>
                        </div>
                    </section>
                </div>

                <aside class="space-y-10">
                    <!-- Total Widget -->
                    <div class="relative overflow-hidden rounded-[2rem] bg-[#1e3a8a] p-8 text-white shadow-xl shadow-blue-900/20">
                        <i class="absolute fas fa-graduation-cap -bottom-6 -right-6 rotate-12 text-9xl opacity-10"></i>
                        <h3 class="relative z-10 mb-1 text-xs font-black tracking-widest uppercase text-blue-200/80">ทรัพยากรทั้งหมด</h3>
                        <p class="relative z-10 text-4xl font-black">{{ stats.total.toLocaleString() }}</p>
                        <p class="relative z-10 mt-2 text-sm text-blue-200/80">รายการที่เผยแพร่แล้วในคลัง</p>
                    </div>

                    <!-- Statistics Widget -->
                    <div class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
                        <h3 class="text-md mb-6 border-b pb-4 font-black uppercase tracking-[0.2em] text-yellow-500">Discover</h3>
                        <div class="space-y-6">
                            <div v-for="cat in stats.byCategory" :key="cat.name">
                                <p class="text-[10px] font-bold uppercase text-slate-400">{{ cat.name }}</p>
                                <p class="text-2xl font-black tracking-tight text-blue-900">{{ cat.count.toLocaleString() }}</p>
                            </div>
                            <p v-if="stats.byCategory.length === 0" class="text-sm text-slate-400">ยังไม่มีข้อมูล</p>
                        </div>
                    </div>
                </aside>
            </div>
        </main>

        <!-- 1.3 External Links & Footer Group -->
        <section class="pt-20 pb-10 bg-white border-t border-slate-200">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-12 mb-16 md:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-6">
                        <div class="flex items-center space-x-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#1e3a8a] text-sm font-black text-white">M</div>
                            <span class="text-xl font-black text-slate-900">MSU IR</span>
                        </div>
                        <p class="text-sm leading-relaxed text-slate-500">
                            คลังทรัพยากรดิจิทัลเพื่อการวิจัยและส่งเสริมความรู้ของท้องถิ่น มุ่งเน้นการแบ่งปันความรู้สู่ระดับสากล
                        </p>
                        <div class="flex space-x-4">
                            <i class="text-lg transition-colors cursor-pointer fab fa-facebook text-slate-300 hover:text-blue-600"></i>
                            <i class="text-lg transition-colors cursor-pointer fab fa-twitter text-slate-300 hover:text-blue-400"></i>
                            <i class="text-lg transition-colors cursor-pointer fas fa-envelope text-slate-300 hover:text-red-500"></i>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">มหาวิทยาลัย</h4>
                        <ul class="space-y-4">
                            <li>
                                <a
                                    href="https://msu.ac.th/"
                                    target="_blank"
                                    class="flex items-center text-sm transition-colors group text-slate-500 hover:scale-105 hover:text-blue-800"
                                >
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    เว็บไซต์มหาวิทยาลัย
                                </a>
                            </li>
                            <li>
                                <a
                                    href="https://regpr.msu.ac.th/"
                                    target="_blank"
                                    class="flex items-center text-sm transition-colors group text-slate-500 hover:scale-105 hover:text-blue-800"
                                >
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    กองทะเบียนและประมวลผล
                                </a>
                            </li>
                            <li>
                                <a
                                    href="https://grad.msu.ac.th/"
                                    target="_blank"
                                    class="flex items-center text-sm transition-colors group text-slate-500 hover:scale-105 hover:text-blue-800"
                                >
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    บัณฑิตวิทยาลัย
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">ห้องสมุดและวิจัย</h4>
                        <ul class="space-y-4">
                            <li>
                                <a
                                    href="https://library.msu.ac.th/"
                                    target="_blank"
                                    class="flex items-center text-sm transition-colors group text-slate-500 hover:scale-105 hover:text-blue-800"
                                >
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    สำนักวิทยบริการ
                                </a>
                            </li>
                            <li>
                                <a
                                    href="https://opac.msu.ac.th/"
                                    target="_blank"
                                    class="flex items-center text-sm transition-colors group text-slate-500 hover:scale-105 hover:text-blue-800"
                                >
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    ระบบสืบค้น WebOPAC
                                </a>
                            </li>
                            <li>
                                <a
                                    href="https://library.msu.ac.th/?page_id=1437"
                                    target="_blank"
                                    class="flex items-center text-sm transition-colors group text-slate-500 hover:scale-105 hover:text-blue-800"
                                >
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    ฐานข้อมูลออนไลน์
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">ช่วยเหลือ</h4>
                        <ul class="space-y-4">
                            <li>
                                <a
                                    href=""
                                    target="_blank"
                                    class="flex items-center text-sm transition-colors group text-slate-500 hover:scale-105 hover:text-blue-800"
                                >
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    คู่มือการใช้งาน
                                </a>
                            </li>
                            <li>
                                <a
                                    href=""
                                    target="_blank"
                                    class="flex items-center text-sm transition-colors group text-slate-500 hover:scale-105 hover:text-blue-800"
                                >
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    คำถามที่พบบ่อย (FAQ)
                                </a>
                            </li>
                            <li>
                                <a
                                    href=""
                                    target="_blank"
                                    class="flex items-center text-sm transition-colors group text-slate-500 hover:scale-105 hover:text-blue-800"
                                >
                                    <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 transition-colors group-hover:text-yellow-500"></i>
                                    แจ้งปัญหาการใช้งาน
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
/* hide the scrollbar on the category slider (still scrollable by drag / buttons / wheel) */
.cat-slider {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.cat-slider::-webkit-scrollbar {
    display: none;
}
</style>
