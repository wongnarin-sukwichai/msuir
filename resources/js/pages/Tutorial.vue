<script setup lang="ts">
// ╔══════════════════════════════════════════════════════════════════════╗
// ║          Vue 3 + TypeScript + Inertia.js — คู่มือฉบับสมบูรณ์        ║
// ║                       MSU IR Project                                ║
// ╚══════════════════════════════════════════════════════════════════════╝
//
// ─── <script setup lang="ts"> คืออะไร? ────────────────────────────────
//
// "setup"   = Composition API แบบ shorthand ของ Vue 3
//             ทุกสิ่งที่ประกาศในนี้ (variables, functions, imports)
//             จะ expose ให้ <template> ใช้ได้อัตโนมัติ ไม่ต้อง return {}
//
// "lang=ts" = บอก Vite ให้ประมวลผล block นี้เป็น TypeScript
//             ทำให้ได้ type-checking และ autocomplete จาก IDE
//
// เปรียบเทียบ:
//   Vue 2 Options API:  export default { data() {}, methods: {}, computed: {} }
//   Vue 3 Composition:  import ref, computed แล้วใช้ตรง ๆ ได้เลย (เรียบง่ายกว่า)


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 1 — IMPORTS
// ═══════════════════════════════════════════════════════════════════════
//
// "@/" คือ path alias ของโฟลเดอร์ /resources/js
// ตั้งค่าไว้ใน vite.config.ts → resolve.alias
// ทำให้เขียน '@/layouts/PublicLayout.vue' แทน '../../layouts/PublicLayout.vue'

import PublicLayout from '@/layouts/PublicLayout.vue';

// ─── Inertia.js คืออะไร และทำงานอย่างไร? ────────────────────────────
//
// Inertia.js เป็น "สะพาน" เชื่อมระหว่าง Laravel (backend) กับ Vue (frontend)
// โดยไม่ต้องสร้าง REST API เลย
//
// ลำดับการทำงานเมื่อเปิดหน้าเว็บ:
//   1. Browser ขอหน้าแรก → Laravel ส่ง HTML page พร้อม JSON data ฝังอยู่
//   2. Vue โหลดขึ้นมา อ่าน JSON data นั้น แล้ว render component
//   3. เมื่อผู้ใช้คลิก <Link> → Inertia intercept (ไม่ให้ browser navigate ปกติ)
//   4. Inertia fetch เฉพาะ JSON ใหม่จาก Laravel (ไม่โหลด HTML ทั้งหน้า)
//   5. Vue re-render เฉพาะส่วนที่เปลี่ยน → ได้ประสบการณ์แบบ SPA
//
import {
  Head,       // จัดการ <title> และ <meta> ใน <head> โดยไม่ reload หน้า
  Link,       // แทน <a href> ทำการ navigate แบบ SPA
  router,     // programmatic navigation เช่น router.visit(), router.post()
  usePage,    // อ่าน shared data จาก Laravel (auth user, flash message, ...)
  useForm,    // จัดการ form + validation errors จาก Laravel อัตโนมัติ
} from '@inertiajs/vue3';

// ─── Vue 3 Reactivity System ─────────────────────────────────────────
//
// Vue 3 ใช้ Proxy ในการ track การอ่าน/เขียนค่า
// เมื่อค่า reactive เปลี่ยน Vue จะ re-render เฉพาะส่วนที่ใช้ค่านั้น
//
import {
  ref,        // ทำให้ค่า primitive (string, number, boolean) กลายเป็น reactive
  computed,   // ค่าที่คำนวณจาก reactive อื่น — update อัตโนมัติ มี cache
  watch,      // เฝ้าดูค่า reactive แล้ว run callback เมื่อเปลี่ยน
  onMounted,  // Lifecycle hook: ทำงานหลัง component mount ลง DOM แล้ว
} from 'vue';


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 2 — TYPESCRIPT INTERFACES & TYPES
// ═══════════════════════════════════════════════════════════════════════
//
// Interface = "พิมพ์เขียว" ของ object บอกว่าต้องมี property อะไรบ้าง
// TypeScript จะแสดง error ทันทีถ้าสร้าง object ที่ไม่ตรง interface
// ช่วยจับ bug ก่อน runtime และทำให้ IDE มี autocomplete ที่ถูกต้อง

interface Item {
  id: number;
  title: string;
  author: string;
  // Union Type: ค่าได้เฉพาะ 3 ตัวเลือกนี้เท่านั้น TypeScript จะ error ถ้าใส่ค่าอื่น
  type: 'วิทยานิพนธ์' | 'งานวิจัย' | 'วารสาร';
  downloads: number;
  isNew?: boolean; // "?" = optional — มีหรือไม่มีก็ได้ (ไม่บังคับ)
}

// Type Alias: ใช้กำหนด type ที่ซับซ้อนหรือ reusable
type Status = 'pending' | 'approved' | 'rejected';


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 3 — PROPS: รับข้อมูลจาก Laravel Controller ผ่าน Inertia
// ═══════════════════════════════════════════════════════════════════════
//
// defineProps() บอก Vue ว่า component นี้รับข้อมูลอะไรบ้างจากภายนอก
//
// สำหรับ Inertia Page Component (ไฟล์ใน pages/):
//   - Props มาจาก Laravel Controller ที่ส่งผ่าน Inertia::render()
//   - ตัวอย่าง PHP: return Inertia::render('Tutorial', ['items' => $items]);
//   - Vue รับ $items เป็น prop ชื่อ "items" อัตโนมัติ
//
// ตอนนี้หน้านี้ไม่มี prop จาก Controller จึงเป็น {} ว่าง
// แต่ถ้ามี ให้เขียนเช่น: defineProps<{ items: Item[]; total: number; }>()
//
defineProps<{}>();


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 4 — INERTIA usePage: อ่าน Shared Data จาก Laravel
// ═══════════════════════════════════════════════════════════════════════
//
// usePage() คืน reactive object ที่มี:
//   - props   = ข้อมูลทั้งหมดจาก Controller + Shared Data
//   - url     = URL ปัจจุบัน
//   - version = Inertia asset version (ใช้ cache busting)
//
// Shared Data กำหนดใน: app/Http/Middleware/HandleInertiaRequests.php
//   public function share(Request $request): array {
//       return ['auth' => ['user' => $request->user()]];
//   }
// → ทุกหน้าจะมี page.props.auth.user โดยอัตโนมัติ

const page = usePage();

// computed() ทำให้ authUser เป็น reactive — อัปเดตเองเมื่อ user login/logout
// "as any" ใช้เพราะยังไม่ได้กำหนด TypeScript type ของ Inertia shared props
const authUser = computed(() => (page.props.auth as any)?.user);

// "?." คือ Optional Chaining ของ JavaScript — ถ้า auth เป็น null/undefined
// จะ return undefined แทนที่จะ throw error


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 5 — ref(): ตัวแปร Reactive ของ Component
// ═══════════════════════════════════════════════════════════════════════
//
// ref() ห่อค่าธรรมดาให้เป็น reactive object
//
// กฎสำคัญ:
//   - ใน <script>   → ต้องเข้าถึงผ่าน .value เช่น searchQuery.value = 'ใหม่'
//   - ใน <template> → Vue auto-unwrap ให้ ไม่ต้องใส่ .value เช่น {{ searchQuery }}
//
// เมื่อ .value เปลี่ยน → Vue re-render ทุก template ที่ใช้ตัวแปรนั้นโดยอัตโนมัติ

const searchQuery = ref<string>('');        // string: ref สำหรับ input text
const isModalOpen = ref<boolean>(false);    // boolean: ref สำหรับ toggle
const currentPage = ref<number>(1);         // number: ref สำหรับ pagination

// ref ที่เก็บ object หรือ array
const selectedIds = ref<number[]>([]);

// ref ที่เก็บ object (ต้องระวัง — mutation ใน nested object ก็ reactive)
const formData = ref<{ title: string; author: string }>({
  title: '',
  author: '',
});


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 6 — computed(): ค่าที่คำนวณจาก Reactive
// ═══════════════════════════════════════════════════════════════════════
//
// computed() มี 2 คุณสมบัติสำคัญ:
//   1. Lazy: คำนวณครั้งแรกเมื่อถูกเรียกใช้จริง (ไม่คำนวณทันทีตอนสร้าง)
//   2. Cached: จำผลลัพธ์ไว้ — ถ้า dependency ไม่เปลี่ยน ไม่คำนวณซ้ำ
//
// ต่างจาก method: method จะคำนวณใหม่ทุกครั้งที่เรียก computed มี cache
//
// Vue track dependency อัตโนมัติ: computed จะรู้ว่าตัวเองอ่าน ref/computed ไหน
// และจะ invalidate cache เมื่อ dependency นั้นเปลี่ยนค่า

const hasSearchQuery = computed(() => searchQuery.value.trim().length > 0);

const pageTitle = computed(() =>
  hasSearchQuery.value ? `ผลการค้นหา: "${searchQuery.value}"` : 'หน้าแรก | MSU IR'
);

// computed ที่มีทั้ง getter และ setter (ใช้น้อยกว่า)
const searchLabel = computed({
  get: () => `กำลังค้นหา: ${searchQuery.value}`,
  set: (val: string) => {
    searchQuery.value = val; // setter ถูกเรียกเมื่อมีการ assign ค่าให้ searchLabel
  },
});


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 7 — watch(): เฝ้าดูการเปลี่ยนแปลงและทำ Side Effect
// ═══════════════════════════════════════════════════════════════════════
//
// watch() ใช้เมื่อต้องการทำ "อะไรบางอย่าง" เมื่อค่าเปลี่ยน เช่น:
//   - fetch ข้อมูลใหม่จาก server
//   - เปลี่ยน document.title
//   - sync กับ localStorage
//
// ต่างจาก computed: watch ไม่คืนค่า ใช้สำหรับ side effect เท่านั้น

watch(searchQuery, (newValue, oldValue) => {
  // newValue = ค่าหลังเปลี่ยน, oldValue = ค่าก่อนเปลี่ยน
  console.log(`ค้นหา: "${oldValue}" → "${newValue}"`);
});

// watch หลายค่าพร้อมกัน (array syntax)
watch([searchQuery, currentPage], ([newQuery, newPage]) => {
  console.log(`ค้นหา "${newQuery}" หน้า ${newPage}`);
});

// watch ที่ทำงานทันทีตอน mount ด้วย (immediate: true)
watch(
  isModalOpen,
  (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
  },
  { immediate: false } // false = รอให้เปลี่ยนก่อนค่อย run (default)
);


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 8 — INERTIA useForm: จัดการ Form Submit ไป Laravel
// ═══════════════════════════════════════════════════════════════════════
//
// useForm() สร้าง reactive form object ที่มีครบทุกอย่าง:
//   - field values  : ค่าของ input แต่ละ field
//   - errors        : validation errors จาก Laravel แยกตาม field name
//   - processing    : true ขณะส่ง request (ใช้ disable ปุ่ม Submit)
//   - wasSuccessful : true ถ้า submit สำเร็จ
//   - reset()       : reset กลับค่า initial
//
// ข้อดีเหนือ fetch/axios:
//   - จัดการ CSRF token ให้อัตโนมัติ (ไม่ต้อง config อะไร)
//   - map errors จาก Laravel ValidationException ไปยัง field ให้อัตโนมัติ
//   - ทำ SPA navigate หลัง success โดยไม่ reload

const form = useForm({
  title: '',    // ชื่อค่า field ต้องตรงกับ name attribute ใน validation rules ของ Laravel
  author: '',
  type: 'วิทยานิพนธ์',
});

const handleSubmit = () => {
  form.post(route('item.store'), {
    // onSuccess: ทำงานเมื่อ Laravel ตอบกลับ 2xx
    onSuccess: () => {
      form.reset(); // reset ค่า field ทั้งหมดกลับ initial
    },
    // onError: ทำงานเมื่อ Laravel ตอบกลับ 422 (Validation Failed)
    // form.errors.title จะมีค่าข้อความ error จาก Laravel โดยอัตโนมัติ
    onError: () => {
      console.log('มี validation error:', form.errors);
    },
    // preserveScroll: ไม่ scroll หน้าขึ้นบนหลัง submit
    preserveScroll: true,
  });
};


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 9 — INERTIA router: Navigate แบบ Programmatic
// ═══════════════════════════════════════════════════════════════════════
//
// ใช้เมื่อต้องการ navigate โดย code (ไม่ใช่ผู้ใช้คลิก <Link>)
// เช่น: หลัง delete สำเร็จ redirect กลับ list, logout แล้ว redirect

const goToItemDetail = (id: number) => {
  // route() = Ziggy helper แปลง named route เป็น URL จริง
  // เช่น route('item.show', 5) → '/item/5'
  // Ziggy อ่าน routes จาก Laravel แล้ว generate ให้ใช้ใน JavaScript
  router.visit(route('item.show', { id }));
};

const logout = async () => {
  // router.post() ส่ง POST request ไป Laravel
  // Laravel logout route รับ POST เท่านั้น (ป้องกัน CSRF)
  router.post(route('logout'));
};


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 10 — LIFECYCLE HOOKS
// ═══════════════════════════════════════════════════════════════════════
//
// Vue Component มี lifecycle ดังนี้:
//   setup()      → ทำงานก่อนสุด (ก่อน mount) ใช้สำหรับ initialize state
//   onMounted()  → หลัง component mount ลง DOM แล้ว DOM พร้อมใช้
//   onUpdated()  → หลัง component re-render ทุกครั้ง
//   onUnmounted()→ ก่อน component ถูกทำลาย (ใช้ cleanup เช่น clearInterval)

onMounted(() => {
  // ณ จุดนี้ DOM พร้อมแล้ว สามารถเข้าถึง DOM element ได้
  // ใช้สำหรับ: fetch initial data, init 3rd party library, etc.
  console.log('Tutorial component mounted เรียบร้อย');
});


// ═══════════════════════════════════════════════════════════════════════
// ส่วนที่ 11 — FUNCTIONS ทั่วไป
// ═══════════════════════════════════════════════════════════════════════
//
// ฟังก์ชันที่ประกาศใน <script setup> expose ให้ template ใช้ได้ทันที
// ไม่ต้อง return เหมือน Vue 2

const toggleModal = () => {
  isModalOpen.value = !isModalOpen.value; // ต้องใช้ .value ใน script เสมอ
};

const handleSearch = () => {
  if (!hasSearchQuery.value) return;
  router.get(route('home'), { q: searchQuery.value }, { preserveState: true });
};
</script>


<template>
  <!--
    ═══════════════════════════════════════════════════════════════════
    ส่วนที่ 12 — TEMPLATE: การเขียน HTML ใน Vue
    ═══════════════════════════════════════════════════════════════════

    <template> คือส่วน HTML ของ component
    Vue ใช้ Directives (v-if, v-for, v-model, ...) เพื่อเชื่อม HTML กับ JavaScript

    กฎพื้นฐาน:
      - ทุก directive ขึ้นต้นด้วย "v-"
      - ":" เป็น shorthand ของ v-bind: (bind ค่า JavaScript)
      - "@" เป็น shorthand ของ v-on: (bind event handler)
      - {{ }} (Mustache syntax) แสดงค่า JavaScript ใน HTML
  -->

  <!--
    PublicLayout: Layout Component
    ─────────────────────────────
    Layout component ทำงานคล้าย "กรอบ" ที่ครอบเนื้อหา
    ทุกอย่างใน <PublicLayout>...</PublicLayout> จะถูกแทรกที่ <slot /> ใน layout
    ทำให้ Navbar, LoginModal, CookieBanner ทำงานได้ทุกหน้าโดยไม่ต้องก้อป

    เปรียบเทียบ: เหมือน @extends('layout') ใน Laravel Blade
  -->
  <PublicLayout>

    <!--
      <Head>: จัดการ <head> ของ browser
      ────────────────────────────────
      <Head> มาจาก Inertia.js ทำให้ set <title> และ <meta> ได้
      โดยไม่ต้องมี full page reload (เหมือน SPA จริง ๆ)

      ถ้าไม่ใช้ Inertia.Head จะต้องทำ document.title = '...' เองใน onMounted
    -->
    <Head title="คู่มือ Vue + TypeScript + Inertia | MSU IR" />

    <!--
      ──────────────────────────────────────────────────────────────
      SECTION: Hero Header
      ──────────────────────────────────────────────────────────────
    -->
    <header class="relative px-4 py-24 overflow-hidden bg-white">

      <!-- Tailwind CSS คืออะไร?
           ─────────────────────
           แต่ละ class ทำงานหน้าที่เดียว เช่น:
             "relative"   → position: relative
             "px-4"       → padding-left: 1rem; padding-right: 1rem
             "py-24"      → padding-top: 6rem; padding-bottom: 6rem
             "overflow-hidden" → overflow: hidden
           ใช้ class สำเร็จรูปแทนการเขียน CSS ตรง ๆ
      -->
      <div class="absolute inset-0 opacity-40">
        <div class="absolute -left-24 -top-24 h-96 w-96 rounded-full bg-yellow-100 blur-[100px]"></div>
        <div class="absolute -bottom-24 -right-24 h-96 w-96 rounded-full bg-blue-100 blur-[100px]"></div>
      </div>

      <div class="relative z-10 max-w-4xl mx-auto text-center">
        <h1 class="mb-6 text-4xl font-black tracking-tight text-slate-900 md:text-5xl">
          คลังปัญญา <span class="text-[#1e3a8a]">MSU</span> <span class="text-yellow-500">IR</span>
          <!--
            "text-[#1e3a8a]" คือ Tailwind Arbitrary Value
            ใส่ค่า CSS ใด ๆ ในวงเล็บ [] ได้เลย
          -->
        </h1>

        <!--
          ── @submit.prevent: Event + Modifier ──────────────────────
          "@submit"   = v-on:submit = ฟัง event "submit" ของ form
          ".prevent"  = event modifier = เรียก event.preventDefault()
                        ป้องกัน browser submit form แบบ traditional (reload หน้า)
          "handleSearch" = ฟังก์ชันที่จะถูกเรียกแทน
        -->
        <form @submit.prevent="handleSearch" class="relative max-w-3xl mx-auto group">
          <div class="absolute inset-y-0 flex items-center pointer-events-none left-6">
            <i class="transition-colors fas fa-search text-slate-400 group-focus-within:text-yellow-500"></i>
          </div>

          <!--
            ── v-model: Two-Way Data Binding ──────────────────────────
            v-model="searchQuery" เชื่อม input กับ ref อัตโนมัติ 2 ทาง:
              1. ref → input: เมื่อ searchQuery.value เปลี่ยน input อัปเดตทันที
              2. input → ref: เมื่อผู้ใช้พิมพ์ searchQuery.value เปลี่ยนทันที

            เทียบเท่ากับการเขียน:
              :value="searchQuery" @input="searchQuery = $event.target.value"
          -->
          <input
            type="text"
            placeholder="ค้นหาชื่อเรื่อง, ผู้แต่ง, หัวข้อวิจัย..."
            v-model="searchQuery"
            class="w-full py-5 text-lg transition-all bg-white border-2 shadow-xl rounded-2xl border-slate-100 px-14 text-slate-900 shadow-slate-200/50 placeholder:text-slate-400 focus:border-yellow-400 focus:outline-none focus:ring-4 focus:ring-yellow-400/10"
          />

          <!--
            ── :disabled: Attribute Binding ────────────────────────────
            ":" คือ shorthand ของ v-bind:
            :disabled="!hasSearchQuery" = bind ค่า computed hasSearchQuery เป็น attribute
            เมื่อ hasSearchQuery เป็น false → ปุ่มถูก disable
          -->
          <button
            type="submit"
            :disabled="!hasSearchQuery"
            class="absolute right-3 top-3 rounded-xl bg-[#1e3a8a] px-8 py-2.5 font-bold text-white shadow-lg transition-all hover:bg-blue-800 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            สืบค้น
          </button>
        </form>

        <!--
          ── v-if / v-else: Conditional Rendering ──────────────────────
          v-if="condition" = แสดง element ถ้า condition เป็น true
          v-else           = แสดงถ้า v-if ข้างบนเป็น false
          Vue จะ create/destroy DOM element จริง ๆ (ต่างจาก v-show ที่แค่ hide)
        -->
        <p v-if="hasSearchQuery" class="mt-4 text-sm text-slate-500">
          กำลังค้นหา: <strong class="text-slate-800">{{ searchQuery }}</strong>
          <!--
            {{ searchQuery }} = Mustache interpolation
            แสดงค่าของ ref ใน HTML — Vue auto-unwrap .value ให้
          -->
        </p>
        <p v-else class="mt-4 text-sm text-slate-400">
          พิมพ์คำค้นหาเพื่อเริ่มต้น
        </p>
      </div>
    </header>


    <!--
      ──────────────────────────────────────────────────────────────
      SECTION: Category Cards
      ──────────────────────────────────────────────────────────────
    -->
    <main class="px-4 py-20 mx-auto max-w-7xl sm:px-6 lg:px-8">
      <section class="mb-24">
        <div class="flex items-center mb-10 space-x-4">
          <div class="h-10 w-1.5 rounded-full bg-yellow-400"></div>
          <h2 class="text-3xl font-black tracking-tight text-slate-900">หมวดหมู่ทรัพยากร</h2>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">

          <!--
            ── v-for: List Rendering ──────────────────────────────────
            v-for="item in items" วน loop render element ซ้ำสำหรับแต่ละ item
            ":key" บังคับใส่เสมอ — ช่วยให้ Vue track ว่า element ไหนคือ element ไหน
                   ค่าต้อง unique ใน list นั้น (ใช้ id เสมอถ้ามี)

            "v-for="i in 6"" = วนจาก 1 ถึง 6 (shorthand สำหรับ array/number)

            เมื่อ array เปลี่ยน (push, splice, ...) Vue จะ re-render เฉพาะ item ที่เปลี่ยน
          -->
          <div
            v-for="i in 6"
            :key="i"
            class="relative shadow-lg cursor-pointer group h-72 bg-slate-200 overflow-hidden rounded-2xl"
          >
            <!--
              ── :src, :alt — Attribute Binding ────────────────────────
              ":src" bind ค่า JavaScript เป็น attribute
              การ concatenate string ใน binding: ':src="url + id"'
              หรือ template literal: ':src="`https://.../${i}`"'
            -->
            <img
              :src="`https://picsum.photos/seed/${i + 70}/800/600`"
              :alt="`หมวดหมู่ที่ ${i}`"
              class="object-cover w-full h-full transition-transform duration-500 group-hover:scale-105"
            />
            <div class="absolute inset-0 flex flex-col justify-end p-8 bg-gradient-to-t from-blue-950/80 to-transparent">
              <h3 class="mb-1 text-2xl font-bold text-white">หมวดหมู่ที่ {{ i }}</h3>
              <p class="text-sm font-medium text-blue-100">Resource Collection {{ i }}</p>
              <div class="w-0 h-1 mt-4 transition-all duration-500 bg-yellow-400 rounded-full group-hover:w-full"></div>
            </div>
          </div>

        </div>
      </section>


      <!--
        ──────────────────────────────────────────────────────────────
        SECTION: Content + Sidebar
        ──────────────────────────────────────────────────────────────
      -->
      <div class="grid grid-cols-1 gap-12 lg:grid-cols-4">
        <div class="space-y-16 lg:col-span-3">

          <!-- Recommended Section -->
          <section>
            <div class="flex items-end justify-between mb-4">
              <h2 class="flex items-center mb-4 text-2xl font-bold text-slate-900">
                <span class="flex items-center justify-center w-10 h-10 mr-4 text-yellow-600 bg-yellow-100 rounded-xl">
                  <i class="fas fa-star"></i>
                </span>
                Recommended
              </h2>
              <!--
                ── @click: Click Event Handler ─────────────────────────
                "@click" = v-on:click = ฟัง click event
                "router.visit(...)" เรียกฟังก์ชัน Inertia navigate ได้เลยใน template
              -->
              <button
                @click="router.visit(route('home'))"
                class="text-sm font-bold text-blue-800 transition-colors hover:text-yellow-600"
              >
                ดูทั้งหมด
              </button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div
                v-for="j in 2"
                :key="j"
                class="p-6 transition-all bg-white border cursor-pointer shadow-sm group rounded-3xl border-slate-100 hover:shadow-md"
              >
                <div class="flex items-start">
                  <div class="flex items-center justify-center w-16 h-20 transition-colors shrink-0 rounded-xl bg-slate-50 text-slate-300 group-hover:bg-blue-50 group-hover:text-blue-600">
                    <i class="text-3xl fas fa-file-pdf"></i>
                  </div>
                  <div class="ml-5">
                    <!--
                      ── :class — Dynamic Class Binding ───────────────────
                      :class รับ object หรือ array ของ class ที่ต้องการเพิ่ม/ลบ

                      Object syntax: { 'class-name': condition }
                        → เพิ่ม class ถ้า condition เป็น true

                      Array syntax: ['class-a', condition ? 'class-b' : 'class-c']
                        → ใส่ทั้ง string และ conditional ได้

                      สามารถผสม static class กับ :class ได้พร้อมกัน
                    -->
                    <h3
                      class="font-bold leading-snug transition-colors line-clamp-2 text-slate-900"
                      :class="{ 'text-blue-800': j === 1 }"
                    >
                      ตัวอย่างผลงานวิจัยเพื่อการพัฒนาท้องถิ่นอย่างยั่งยืน ลำดับที่ {{ j }}
                    </h3>
                    <p class="mt-2 text-xs text-slate-500">นักวิจัย มหาวิทยาลัยมหาสารคาม</p>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- New Release Section -->
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
              <div
                v-for="n in 5"
                :key="n"
                class="flex items-center p-6 transition-colors cursor-pointer group hover:bg-slate-50"
              >
                <div class="flex items-center justify-center w-10 h-10 mr-6 transition-colors rounded-lg shrink-0 bg-slate-100 text-slate-400 group-hover:bg-blue-200 group-hover:text-blue-700">
                  <i class="text-sm fas fa-clock"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="text-base font-bold tracking-tight truncate transition-colors text-slate-800 group-hover:text-blue-900">
                    <!--
                      Template Literals ใน Mustache: {{ `string ${variable}` }}
                    -->
                    รายงานวิจัยฉบับสมบูรณ์ประจำปีงบประมาณ 2569 ลำดับที่ {{ n }}
                  </h3>
                  <p class="mt-1 text-xs text-slate-400">อัปเดตเมื่อ: 2 ชั่วโมงที่ผ่านมา | โดย คณะศิลปกรรมศาสตร์</p>
                </div>
                <i class="ml-4 transition-colors fas fa-chevron-right text-slate-200 group-hover:text-blue-900"></i>
              </div>
            </div>
          </section>

        </div>

        <!-- Sidebar -->
        <aside class="space-y-10">
          <!-- Widget: Search Assistant -->
          <div class="relative overflow-hidden rounded-[2rem] bg-[#1e3a8a] p-8 text-white shadow-xl shadow-blue-900/20">
            <i class="absolute fas fa-graduation-cap -bottom-6 -right-6 rotate-12 text-9xl opacity-10"></i>
            <h3 class="relative z-10 mb-3 text-xl font-bold">สืบค้นขั้นสูง</h3>
            <p class="relative z-10 mb-6 text-sm leading-relaxed text-blue-200/80">ค้นหาผลงานตามปีการศึกษา ผู้แต่ง หรือสาขาวิชาเจาะจง</p>

            <!--
              ── v-show vs v-if ────────────────────────────────────────
              v-show="condition" = toggle CSS display:none เท่านั้น (ไม่ destroy DOM)
              v-if="condition"   = สร้าง/ทำลาย DOM element จริง ๆ

              ใช้ v-show เมื่อ toggle บ่อย (performance ดีกว่า ไม่ต้อง re-create)
              ใช้ v-if  เมื่อไม่ค่อย toggle หรือเนื้อหาหนัก (ไม่ render ถ้าไม่จำเป็น)

              ตัวอย่าง: modal ที่เปิด/ปิดบ่อย → ใช้ v-show
                       section ที่ขึ้นกับ auth user → ใช้ v-if
            -->
            <p v-show="!!authUser" class="text-xs text-yellow-300 mb-3">
              สวัสดี {{ authUser?.name }} — คุณเข้าสู่ระบบแล้ว
            </p>

            <button class="relative z-10 rounded-xl bg-yellow-400 px-6 py-2.5 text-xs font-black text-[#1e3a8a] shadow-lg transition-all hover:bg-white">
              เริ่มสืบค้น
            </button>
          </div>

          <!-- Widget: Statistics -->
          <div class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
            <h3 class="text-md mb-6 border-b pb-4 font-black uppercase tracking-[0.2em] text-yellow-500">Discover</h3>
            <div class="space-y-6">
              <div v-for="(stat, index) in [
                { label: 'Social Sciences', count: 1362 },
                { label: 'Education', count: 994 },
                { label: 'Arts and Humanities', count: 984 },
                { label: 'Education Science', count: 280 },
              ]" :key="index">
                <!--
                  v-for กับ object ที่กำหนด inline ได้เลย
                  "stat" คือแต่ละ object ใน array
                  "index" คือ index (0, 1, 2, ...) — optional
                -->
                <p class="text-[10px] font-bold uppercase text-slate-400">{{ stat.label }}</p>
                <p class="text-2xl font-black tracking-tight text-slate-800">{{ stat.count.toLocaleString() }}</p>
                <!--
                  .toLocaleString() = JavaScript method เพิ่ม comma เช่น 1362 → 1,362
                  สามารถเรียก JavaScript method ได้โดยตรงใน {{ }}
                -->
              </div>
            </div>
          </div>
        </aside>
      </div>
    </main>


    <!--
      ──────────────────────────────────────────────────────────────
      SECTION: Form Example (useForm)
      ──────────────────────────────────────────────────────────────
    -->
    <section class="py-16 bg-slate-50 border-t border-slate-100">
      <div class="max-w-lg mx-auto px-4">
        <h2 class="text-2xl font-black text-slate-900 mb-8">ตัวอย่าง Form + Inertia useForm</h2>

        <!--
          form.processing = true ขณะ submit อยู่ (Inertia set ให้อัตโนมัติ)
          ใช้ disable ปุ่มระหว่างรอ response ป้องกัน double submit
        -->
        <form @submit.prevent="handleSubmit" class="space-y-5 bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">

          <div>
            <label class="block mb-2 text-xs font-black uppercase tracking-widest text-slate-400">ชื่อผลงาน</label>
            <input
              type="text"
              v-model="form.title"
              class="w-full px-5 py-4 rounded-2xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-blue-900 focus:bg-white transition-all"
              :class="{ 'border-red-400 bg-red-50': form.errors.title }"
            />
            <!--
              form.errors.title = validation error จาก Laravel
              Inertia map errors จาก ValidationException ให้อัตโนมัติ
              ตรง field name เป๊ะ: 'title' → form.errors.title
            -->
            <p v-if="form.errors.title" class="mt-1.5 text-xs text-red-500 font-bold">
              {{ form.errors.title }}
            </p>
          </div>

          <div>
            <label class="block mb-2 text-xs font-black uppercase tracking-widest text-slate-400">ผู้จัดทำ</label>
            <input
              type="text"
              v-model="form.author"
              class="w-full px-5 py-4 rounded-2xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-blue-900 focus:bg-white transition-all"
              :class="{ 'border-red-400 bg-red-50': form.errors.author }"
            />
            <p v-if="form.errors.author" class="mt-1.5 text-xs text-red-500 font-bold">
              {{ form.errors.author }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-[#1e3a8a] text-white py-4 rounded-2xl font-black text-sm shadow-lg shadow-blue-900/10 active:scale-95 transition-all disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <!--
              Ternary operator ใน template: condition ? 'ถ้าจริง' : 'ถ้าเท็จ'
            -->
            {{ form.processing ? 'กำลังบันทึก...' : 'บันทึกข้อมูล' }}
          </button>

        </form>
      </div>
    </section>


    <!--
      ──────────────────────────────────────────────────────────────
      SECTION: <Link> Component (Inertia Navigation)
      ──────────────────────────────────────────────────────────────
    -->
    <section class="py-16 bg-white border-t border-slate-100">
      <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-black text-slate-900 mb-8">ตัวอย่าง &lt;Link&gt; vs &lt;a&gt;</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div class="bg-green-50 border border-green-200 p-6 rounded-2xl">
            <p class="text-xs font-black uppercase tracking-widest text-green-600 mb-3">✓ ใช้แบบนี้ (Inertia Link)</p>
            <code class="text-sm text-slate-700 block mb-3 bg-white p-3 rounded-xl border border-slate-100">
              &lt;Link href="/category/1"&gt;คณะวิทย์&lt;/Link&gt;
            </code>
            <p class="text-xs text-slate-600">
              → Navigate แบบ SPA: fetch JSON, Vue re-render<br/>
              → ไม่ reload หน้า, ไม่ reload CSS/JS ใหม่<br/>
              → Browser history ถูกต้อง, ปุ่ม Back ใช้ได้
            </p>
          </div>

          <div class="bg-red-50 border border-red-200 p-6 rounded-2xl">
            <p class="text-xs font-black uppercase tracking-widest text-red-600 mb-3">✗ หลีกเลี่ยง (HTML a tag)</p>
            <code class="text-sm text-slate-700 block mb-3 bg-white p-3 rounded-xl border border-slate-100">
              &lt;a href="/category/1"&gt;คณะวิทย์&lt;/a&gt;
            </code>
            <p class="text-xs text-slate-600">
              → Full page reload: โหลด HTML, CSS, JS ใหม่ทั้งหมด<br/>
              → เสีย state ทั้งหมดของ Vue application<br/>
              → ช้ากว่าและประสบการณ์แย่กว่า
            </p>
          </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-4">
          <!--
            <Link> รับ prop "href" และ "method" (default = GET)
            สำหรับ DELETE/POST ใช้ method prop แทน router.xxx()
          -->
          <Link :href="route('home')" class="bg-[#1e3a8a] text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-blue-800 transition-colors">
            หน้าแรก (GET)
          </Link>
          <Link :href="route('category.show', { id: 1 })" class="bg-yellow-400 text-slate-900 px-6 py-3 rounded-xl text-sm font-bold hover:bg-yellow-300 transition-colors">
            หมวดหมู่ที่ 1
          </Link>
        </div>
      </div>
    </section>


    <!--
      ──────────────────────────────────────────────────────────────
      SECTION: Footer
      ──────────────────────────────────────────────────────────────
    -->
    <section class="pt-20 pb-10 bg-white border-t border-slate-200">
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-12 mb-16 md:grid-cols-2 lg:grid-cols-4">
          <div class="space-y-6">
            <div class="flex items-center space-x-3">
              <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#1e3a8a] text-sm font-black text-white">M</div>
              <span class="text-xl font-black text-slate-900">MSU IR</span>
            </div>
            <p class="text-sm leading-relaxed text-slate-500">
              คลังทรัพยากรดิจิทัลเพื่อการวิจัยและส่งเสริมความรู้ของท้องถิ่น
            </p>
          </div>

          <div>
            <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">มหาวิทยาลัย</h4>
            <ul class="space-y-4">
              <li>
                <!--
                  ใช้ <a> ที่นี่ถูกต้อง เพราะเป็น external link (ออกนอก app)
                  target="_blank" + rel="noopener noreferrer" = security best practice
                -->
                <a href="https://msu.ac.th/" target="_blank" rel="noopener noreferrer"
                   class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                  <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 group-hover:text-yellow-500"></i>
                  เว็บไซต์มหาวิทยาลัย
                </a>
              </li>
              <li>
                <a href="https://regpr.msu.ac.th/" target="_blank" rel="noopener noreferrer"
                   class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                  <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 group-hover:text-yellow-500"></i>
                  กองทะเบียนและประมวลผล
                </a>
              </li>
            </ul>
          </div>

          <div>
            <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">ห้องสมุดและวิจัย</h4>
            <ul class="space-y-4">
              <li>
                <a href="https://library.msu.ac.th/" target="_blank" rel="noopener noreferrer"
                   class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                  <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 group-hover:text-yellow-500"></i>
                  สำนักวิทยบริการ
                </a>
              </li>
              <li>
                <a href="https://opac.msu.ac.th/" target="_blank" rel="noopener noreferrer"
                   class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                  <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 group-hover:text-yellow-500"></i>
                  ระบบสืบค้น WebOPAC
                </a>
              </li>
            </ul>
          </div>

          <div>
            <h4 class="mb-6 text-lg font-black tracking-widest uppercase text-slate-900">ช่วยเหลือ</h4>
            <ul class="space-y-4">
              <li>
                <a href="" class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                  <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 group-hover:text-yellow-500"></i>
                  คู่มือการใช้งาน
                </a>
              </li>
              <li>
                <a href="" class="flex items-center text-sm transition-colors group text-slate-500 hover:text-blue-800">
                  <i class="fas fa-chevron-right mr-3 text-[10px] text-slate-200 group-hover:text-yellow-500"></i>
                  คำถามที่พบบ่อย (FAQ)
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
