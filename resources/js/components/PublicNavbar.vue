<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const emit = defineEmits<{ (e: 'open-login'): void }>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);

const isCollectionOpen = ref(false);
const isMobileMenuOpen = ref(false);

interface NavLink {
    name: string;
    href: string;
}

const navLinks: NavLink[] = [
    { name: 'Home', href: '/' },
    { name: 'Collection', href: '#' },
    { name: 'About', href: '#' },
    { name: 'Contact', href: '#' },
];

// TODO: เปลี่ยนมาอ่านจาก usePage().props.collections เมื่อ backend พร้อม
const collectionGroups = [
    {
        title: 'Institutional Repository (MSU-IR)',
        links: [
            { name: 'MSU e-Theses', href: '/collection/1' },
            { name: 'MSU e-Independent Studies (IS)', href: '#' },
            { name: 'MSU e-Senior Projects', href: '#' },
            { name: 'MSU e-Researches', href: '#' },
            { name: 'MSU e-Books', href: '#' },
            { name: 'MSU e-Articles', href: '#' },
        ],
    },
    {
        title: 'Archive and Rare books',
        links: [
            { name: 'Local Wisdom Collection', href: '#' },
            { name: 'Rare Books', href: '#' },
            { name: 'Manuscripts', href: '#' },
            { name: 'Historical Photographs', href: '#' },
        ],
    },
    {
        title: 'Multimedia & E-Learning',
        links: [
            { name: 'MSU Multimedia Archives', href: '#' },
            { name: 'E-Lecture Series', href: '#' },
            { name: 'Research Reports', href: '#' },
            { name: 'Free e-Books', href: '#' },
        ],
    },
];

watch(isMobileMenuOpen, (val) => {
    if (!val) isCollectionOpen.value = false;
});
</script>

<template>
    <!-- ===== NAVBAR ===== -->
    <nav class="sticky top-0 z-[60] border-b border-slate-200 bg-white/80 backdrop-blur-md">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center space-x-4 shrink-0">
                    <a href="/" class="flex items-center space-x-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#1e3a8a] text-xl font-black text-white shadow-lg shadow-blue-900/20">
                            M
                        </div>
                        <div class="hidden sm:block">
                            <span class="block font-bold leading-tight uppercase text-md text-slate-900">Institutional Repository</span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-blue-800">Maha Sarakham University</span>
                        </div>
                    </a>
                </div>

                <div class="items-center hidden space-x-2 lg:flex">
                    <template v-for="link in navLinks" :key="link.name">
                        <button
                            v-if="link.name === 'Collection'"
                            @click="isCollectionOpen = !isCollectionOpen"
                            :class="[
                                'flex items-center rounded-xl px-4 py-2 text-sm font-bold transition-all',
                                isCollectionOpen ? 'bg-blue-50 text-blue-800' : 'text-slate-600 hover:bg-slate-50 hover:text-[#1e3a8a]',
                            ]"
                        >
                            {{ link.name }}
                            <i :class="['fas fa-chevron-down ml-2 text-[10px] transition-transform', isCollectionOpen ? 'rotate-180' : '']"></i>
                        </button>
                        <a
                            v-else
                            :href="link.href ?? '#'"
                            class="rounded-xl px-4 py-2 text-sm font-bold text-slate-600 transition-all hover:bg-slate-50 hover:text-[#1e3a8a]"
                        >
                            {{ link.name }}
                        </a>
                    </template>
                </div>

                <div class="flex items-center space-x-4 lg:space-x-8">
                    <div class="items-center hidden space-x-3 text-sm font-bold sm:flex">
                        <button type="button" class="text-blue-900 border-b-2 border-yellow-400">TH</button>
                        <span class="text-slate-300">|</span>
                        <button type="button" class="transition text-slate-400 hover:text-blue-900">EN</button>
                    </div>

                    <transition name="fade" mode="out-in">
                        <div
                            v-if="!user"
                            @click="emit('open-login')"
                            class="group flex cursor-pointer items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 transition-all duration-300 hover:bg-yellow-400 hover:text-black lg:px-6 lg:py-2.5"
                        >
                            <i class="text-lg fas fa-user-circle lg:mr-2"></i>
                            <span class="hidden lg:inline">เข้าสู่ระบบ</span>
                        </div>

                        <div v-else-if="user && user.role_level === 1" class="flex items-center gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 font-black bg-yellow-400 border-2 border-white rounded-full shadow-sm text-slate-900">
                                    {{ user.name.charAt(0) }}
                                </div>
                                <div class="hidden lg:block">
                                    <p class="mb-0.5 text-xs font-medium text-slate-500">ยินดีต้อนรับ</p>
                                    <p class="text-sm font-bold leading-none text-slate-800">{{ user.name }}</p>
                                </div>
                            </div>

                            <div class="h-8 w-[1px] bg-slate-200"></div>

                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-white transition-all duration-300 rounded-full bg-slate-800 hover:bg-red-500 hover:shadow-lg active:scale-95"
                            >
                                <span>ออกจากระบบ</span>
                                <i class="text-xs fas fa-sign-out-alt"></i>
                            </Link>
                        </div>

                        <Link v-else :href="route('dashboard')" class="group flex cursor-pointer items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 transition-all duration-300 hover:bg-yellow-400 hover:text-black lg:px-6 lg:py-2.5">
                            <i class="text-md fas fa-database lg:mr-2"></i>
                            <span class="hidden lg:inline">ไปที่หน้าจัดการระบบ</span>
                        </Link>
                    </transition>

                    <button
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-50 text-slate-600 hover:bg-slate-100 lg:hidden"
                    >
                        <i :class="['fas text-xl', isMobileMenuOpen ? 'fa-times' : 'fa-bars']"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- ===== COLLECTION DROPDOWN PANEL ===== -->
    <transition name="fade" mode="out-in">
        <div
            v-if="isCollectionOpen"
            class="fixed z-[55] hidden w-full overflow-hidden border-b border-slate-200 shadow-2xl bg-white transition-opacity lg:block"
        >
            <div class="px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900">รายการคอลเลกชัน</h2>
                        <p class="mt-1 text-sm text-slate-500">เลือกหมวดหมู่ที่ต้องการเพื่อเข้าถึงคลังทรัพยากรดิจิทัล</p>
                    </div>
                    <button
                        @click="isCollectionOpen = false"
                        class="flex items-center justify-center w-10 h-10 transition-all rounded-full bg-slate-100 text-slate-400 hover:bg-slate-200 hover:text-slate-600"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-12 md:grid-cols-3">
                    <div v-for="group in collectionGroups" :key="group.title" class="flex flex-col">
                        <h3 class="mb-6 text-[15px] font-bold uppercase tracking-wider text-blue-900">
                            {{ group.title }}
                        </h3>
                        <ul class="space-y-3">
                            <li v-for="link in group.links" :key="link.name">
                                <a
                                    :href="link.href ?? '#'"
                                    class="group relative inline-block w-fit text-[15px] text-slate-700 transition-all hover:text-blue-900"
                                >
                                    <span class="relative z-10">{{ link.name }}</span>
                                    <span class="absolute bottom-0 left-0 h-[2px] w-0 bg-yellow-400 transition-all duration-300 ease-in-out group-hover:w-full"></span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </transition>

    <!-- ===== MOBILE MENU ===== -->
    <transition name="fade">
        <div v-if="isMobileMenuOpen" class="fixed inset-0 z-40 pt-20 bg-white lg:hidden">
            <div class="h-full p-6 overflow-y-auto">
                <div class="flex flex-col space-y-2">
                    <template v-for="link in navLinks" :key="link.name">
                        <a
                            v-if="link.name !== 'Collection'"
                            :href="link.href ?? '#'"
                            class="py-4 text-xl font-bold border-b border-slate-50 text-slate-800"
                        >
                            {{ link.name }}
                        </a>

                        <div v-else class="flex flex-col">
                            <button
                                @click="isCollectionOpen = !isCollectionOpen"
                                class="flex items-center justify-between py-4 text-xl font-bold border-b border-slate-50 text-slate-800"
                            >
                                {{ link.name }}
                                <i :class="['fas fa-chevron-down transition-transform', isCollectionOpen ? 'rotate-180' : '']"></i>
                            </button>

                            <div v-if="isCollectionOpen" class="p-6 mt-2 space-y-6 rounded-2xl bg-slate-50">
                                <div v-for="group in collectionGroups" :key="group.title">
                                    <h4 class="mb-3 text-[14px] font-black uppercase text-blue-900">{{ group.title }}</h4>
                                    <ul class="space-y-4">
                                        <li v-for="sublink in group.links" :key="sublink.name">
                                            <a :href="sublink.href" class="text-[14px] text-slate-600">{{ sublink.name }}</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="flex items-center pt-8 space-x-4 font-bold">
                        <button type="button" class="text-blue-900 border-b-2 border-yellow-400">TH</button>
                        <span class="text-slate-300">|</span>
                        <button type="button" class="transition text-slate-400 hover:text-blue-900">EN</button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
